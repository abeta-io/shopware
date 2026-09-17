<?php
declare(strict_types=1);

namespace MagmodulesAbeta\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Local-debugging only
 *
 * Only reachable when APP_ENV is not prod, or when the plugin's debug mode is on.
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class FakeErpController extends StorefrontController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SystemConfigService $systemConfigService,
        private readonly RouterInterface $router,
        private readonly string $storageDir,
        private readonly string $appEnv,
    ) {
    }

    #[Route(path: '/abeta/fake-erp', name: 'frontend.abeta.fake-erp.index', methods: ['GET'])]
    public function index(Request $request, SalesChannelContext $context): Response
    {
        $this->assertEnabled($context);

        $apiKey = (string) ($this->systemConfigService->get('MagmodulesAbeta.config.abetaApi', $context->getSalesChannelId()) ?? '');
        $sessionId = 'erp-' . bin2hex(random_bytes(4));
        $returnUrl = $this->router->generate('frontend.abeta.fake-erp.receive', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $loginUrl = $this->router->generate('frontend.abeta.fake-erp.punchout', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $receivedUrl = $this->router->generate('frontend.abeta.fake-erp.receive', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $error = $request->query->get('error');

        $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES);

        $errorHtml = $error ? '<p class="err">' . $e((string) $error) . '</p>' : '';

        $html = <<<HTML
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Fake ERP - Abeta punchout</title>
<style>
 body{font-family:system-ui,sans-serif;max-width:640px;margin:40px auto;padding:0 16px;color:#222}
 h1{font-size:20px} label{display:block;margin:12px 0 4px;font-weight:600;font-size:13px}
 input{width:100%;padding:8px;border:1px solid #bbb;border-radius:6px;box-sizing:border-box}
 button{margin-top:20px;padding:10px 18px;background:#2b6;color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:15px}
 .err{background:#fdd;border:1px solid #c66;padding:10px;border-radius:6px;color:#900}
 .hint{color:#666;font-size:12px;margin-top:24px}
 a{color:#2b6}
</style></head><body>
<h1>Fake ERP &mdash; start Abeta punchout</h1>
{$errorHtml}
<form method="post" action="{$e($loginUrl)}">
  <label>Username (Shopware customer email)</label>
  <input name="username" type="text" autocomplete="off" required>
  <label>Password</label>
  <input name="password" type="password" autocomplete="off" required>
  <label>Session ID</label>
  <input name="session_id" type="text" value="{$e($sessionId)}" required>
  <label>API key</label>
  <input name="api_key" type="text" value="{$e($apiKey)}" required>
  <label>Return URL (this fake ERP)</label>
  <input name="return_url" type="text" value="{$e($returnUrl)}" required>
  <button type="submit">Punch out &rarr;</button>
</form>
<p class="hint">After login, shop as usual and press the Abeta return button. The cart export
lands at <a href="{$e($receivedUrl)}">{$e($receivedUrl)}</a> &mdash; open it any time to inspect captured payloads.</p>
</body></html>
HTML;

        return new Response($html);
    }

    #[Route(path: '/abeta/fake-erp/punchout', name: 'frontend.abeta.fake-erp.punchout', methods: ['POST'])]
    public function punchout(Request $request, SalesChannelContext $context): Response
    {
        $this->assertEnabled($context);

        $payload = [
            'username' => (string) $request->request->get('username'),
            'password' => (string) $request->request->get('password'),
            'session_id' => (string) $request->request->get('session_id'),
            'api_key' => (string) $request->request->get('api_key'),
            'return_url' => (string) $request->request->get('return_url'),
        ];

        $loginUrl = $this->router->generate('frontend.v1.abeta.login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $response = $this->httpClient->request('POST', $loginUrl, [
                'json' => $payload,
                'timeout' => 30,
            ]);
            $data = json_decode($response->getContent(false), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $t) {
            return $this->redirectToErp('Login request failed: ' . $t->getMessage());
        }

        if (($data['type'] ?? null) !== 'success' || empty($data['one_time_url'])) {
            return $this->redirectToErp($data['message'] ?? 'Login failed.');
        }

        return new Response('', Response::HTTP_FOUND, ['Location' => (string) $data['one_time_url']]);
    }

    #[Route(path: '/abeta/fake-erp/receive', name: 'frontend.abeta.fake-erp.receive', methods: ['GET', 'POST'])]
    public function receive(Request $request, SalesChannelContext $context): Response
    {
        $this->assertEnabled($context);

        if ($request->isMethod('POST')) {
            return $this->storePayload($request);
        }

        return $this->renderReceived();
    }

    private function storePayload(Request $request): Response
    {
        $raw = $request->getContent();
        $decoded = json_decode($raw, true);

        $sessionId = \is_array($decoded) ? ($decoded['general']['session_id'] ?? null) : null;
        $slug = $sessionId ? preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $sessionId) : 'nosession';
        $filename = sprintf('%s-%s.json', date('Ymd-His'), $slug);

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0775, true);
        }

        // Store the pretty-printed payload if it parsed, otherwise the raw body.
        $body = \is_array($decoded)
            ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : $raw;
        file_put_contents($this->storageDir . '/' . $filename, $body);

        return new JsonResponse(['type' => 'success', 'stored' => $filename]);
    }

    private function renderReceived(): Response
    {
        $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES);

        $files = is_dir($this->storageDir) ? glob($this->storageDir . '/*.json') : [];
        rsort($files); // newest first (filenames are timestamp-prefixed)

        $blocks = '';
        foreach (\array_slice($files, 0, 25) as $file) {
            $name = basename($file);
            $content = (string) file_get_contents($file);
            $blocks .= '<h2>' . $e($name) . '</h2><pre>' . $e($content) . '</pre>';
        }

        if ($blocks === '') {
            $blocks = '<p class="hint">No payloads captured yet. Start a punchout, add products, and press the Abeta return button.</p>';
        }

        $indexUrl = $this->router->generate('frontend.abeta.fake-erp.index', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $html = <<<HTML
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Fake ERP - received payloads</title>
<style>
 body{font-family:system-ui,sans-serif;max-width:900px;margin:40px auto;padding:0 16px;color:#222}
 h1{font-size:20px} h2{font-size:14px;margin-top:28px;color:#2b6}
 pre{background:#0f1720;color:#d6e2ef;padding:14px;border-radius:8px;overflow:auto;font-size:12px;line-height:1.5}
 .hint{color:#666;font-size:13px} a{color:#2b6}
</style></head><body>
<h1>Fake ERP &mdash; captured cart exports</h1>
<p class="hint"><a href="{$e($indexUrl)}">&larr; start another punchout</a></p>
{$blocks}
</body></html>
HTML;

        return new Response($html);
    }

    private function redirectToErp(string $error): Response
    {
        $url = $this->router->generate(
            'frontend.abeta.fake-erp.index',
            ['error' => $error],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new Response('', Response::HTTP_FOUND, ['Location' => $url]);
    }

    private function assertEnabled(SalesChannelContext $context): void
    {
        $debug = (bool) $this->systemConfigService->get('MagmodulesAbeta.config.debug', $context->getSalesChannelId());

        if ($this->appEnv === 'prod' && !$debug) {
            throw new NotFoundHttpException();
        }
    }
}
