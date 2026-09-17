import SelectedProductPrice from './select-product-price-cart/selected-product-price.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('SelectedProductPrice', SelectedProductPrice, '[data-select-product-price]');
