# 6.1.0
- Add `AbetaCartExportEvent`: dispatched after the cart export data is assembled, so other plugins can modify the exported data

# 6.0.0
- Shopware 6.6 compatibility
- Add Technical Name from shipping method as sku for shipping costs
- Punchout sessions are unique now and don't share the cart with the regular account or other punchout sessions
- Add /v1/abeta/itemdata endpoint: returns a recalculated cart with customer-specific pricing and shipping methods for a given email and SKU/qty list
- Add "export at checkout confirm step" config option: lets the customer fill in a personal delivery address during checkout before the cart is exported to Abeta, instead of exporting straight from the cart page
