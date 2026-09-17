import HttpClient from 'src/service/http-client.service';
import Plugin from 'src/plugin-system/plugin.class';

export default class SelectProductPrice extends Plugin {

    static options = {
        stickyConfigSelector: 'sticky-ict-configuration',
        stickyDivSelector: '.sticky-ict-offer',
        stickyGenSelector: '.sticky-ict-general',
        addToCartSelector: '.btn-buy',
        openToCartSelector: '.header-cart-btn',
        canToCartSelector: 'cart-offcanvas',
        timerSelector: '.ictTimer',
        offcanvasSelector: '.offcanvas',
        cookieName: 'countDown',

    };

    init() {
        // initialize the HttpClient
        this._httpClient = new HttpClient();
        this._formElement = this.el;
        this._popupParent = this.el.closest('[data-select-product-price]');
        const bgLayer = document.querySelector('#allProductSelect');
        const singleProductSelect1 = document.querySelectorAll('.singleProductSelect');
        const productIds = [];
        let newArray = null;

        let selectedProductPrice = 0;
        // var total = 0;
        for (const tnsOuterNavElementw1 of singleProductSelect1) {
            tnsOuterNavElementw1.addEventListener('change', event => {
                if (event.target.checked) {
                    let singleProductSelect2 = document.querySelectorAll('.singleProductSelect');
                    for (const tnsOuterNavElementw1 of singleProductSelect2) {
                        if (tnsOuterNavElementw1.checked == true) {
                            tnsOuterNavElementw1.setAttribute("data-check", "1")
                            selectedProductPrice += parseFloat(tnsOuterNavElementw1.value);
                            productIds.push(tnsOuterNavElementw1.getAttribute("data-product-id"));

                            newArray = productIds.filter((value, index, self) => self.indexOf(value) === index);
                        }
                    }
                    let newAr1ray = productIds.filter((value, index, self) => self.indexOf(value) === index);

                    let initialCheckout = {
                        eventName: "allProductSelectq",
                        value: selectedProductPrice,
                        productIds: newArray,
                        checkedprod: event.target.checked
                    };
                    this._httpClient.post(window.customRouter['frontend.checkout.line-items.update'], JSON.stringify(initialCheckout));
                } else if (event.target.checked == false) {
                    let newPrice = 0;
                    let newProductIds = [];
                    newProductIds.push(event.target.getAttribute("data-product-id"));

                    let initialCheckout = {
                        eventName: "allProductSelectqhfjdhf",
                        value: newPrice,
                        productIds: newProductIds,
                        checkedprod: event.target.checked
                    };
                    this._httpClient.post(window.customRouter['frontend.checkout.line-items.update'], JSON.stringify(initialCheckout));
                }
                // location.reload();
            });
        }
        bgLayer.addEventListener('change', event => {
            if (event.target.checked) {
                event.preventDefault();
                const productIds1 = [];
                // this._logCheckboxState(bgLayer, 1);
                let singleProductSelect = document.querySelectorAll('.singleProductSelect');
                for (const tnsOuterNavElement1 of singleProductSelect) {
                    productIds1.push(tnsOuterNavElement1.getAttribute("data-product-id"));
                }

                let initialCheckout = {
                    eventName: "allProductSelect",
                    // value : totalPrice,
                    productIds: productIds1,
                    checkedprod: event.target.checked
                };

                this._httpClient.post(window.customRouter['frontend.checkout.line-items.update'], JSON.stringify(initialCheckout));
            } else if (event.target.checked == false) {
                let newPrice = 0;
                let newProductIds = [];
                newProductIds.push(event.target.getAttribute("data-product-id"));

                let initialCheckout = {
                    eventName: "allProductSelectqhfjdhf",
                    value: newPrice,
                    productIds: newProductIds,
                    checkedprod: event.target.checked
                };
                this._httpClient.post(window.customRouter['frontend.checkout.line-items.update'], JSON.stringify(initialCheckout));
            }
            // location.reload();
        });
        this._registerEvents();
    }

    _registerEvents() {
        //when first time add to cart click call timer start

        if (document.querySelectorAll(".cart-offcanvas").length > 0) {
            // const addToCartClick = DomAccess.querySelectorAll('cart-offcanvas');
            const addToCartClick1 = document.querySelectorAll('.cart-offcanvas');

            if (addToCartClick1) {
                this._firstAddToCartClick()
            }
            // addToCartClick1.forEach((element) => {
            //     this._firstAddToCartClick.bind(this)
            //         // element.addEventListener('click', this._firstAddToCartClick.bind(this));
            //     });
            // }
        }
    }

    _firstAddToCartClick(event) {
        const singleProductSelect12 = document.querySelectorAll('.singleProductSelect1');
        const productIds2 = [];
        let newArray2 = null;
        for (const tnsOuterNavElementw11 of singleProductSelect12) {
            tnsOuterNavElementw11.addEventListener('change', event => {
                if (event.target.checked) {
                    let singleProductSelect22 = document.querySelectorAll('.singleProductSelect1');
                    for (const tnsOuterNavElementw11 of singleProductSelect22) {
                        if (tnsOuterNavElementw11.checked == true) {
                            tnsOuterNavElementw11.setAttribute("data-check", "1")
                            productIds2.push(tnsOuterNavElementw11.getAttribute("data-product-id"));
                            newArray2 = productIds2.filter((value, index, self) => self.indexOf(value) === index);
                        }
                    }
                    let newAr1ray1 = productIds2.filter((value, index, self) => self.indexOf(value) === index);
                    let initialCheckout = {
                        eventName: "allProductSelectqOffCanvas",
                        productIds2: newAr1ray1,
                        checkedprod: event.target.checked
                    };
                    this._httpClient.post(window.customRouter['frontend.checkout.line-items.update'], JSON.stringify(initialCheckout));
                } else if (event.target.checked == false) {
                    let newPrice = 0;
                    let newProductIds = [];
                    newProductIds.push(event.target.getAttribute("data-product-id"));
                    let initialCheckout = {
                        eventName: "allProductSelectqhfjdhf",
                        value: newPrice,
                        productIds: newProductIds,
                        checkedprod: event.target.checked
                    };
                    this._httpClient.post(window.customRouter['frontend.checkout.line-items.update'], JSON.stringify(initialCheckout));
                }
                // location.reload();
            });
        }
        const bgOffcanvas = document.querySelector('allProductOffcanvas');
        bgOffcanvas.addEventListener('change', event => {
            if (event.target.checked) {
                event.preventDefault();
                const productIds1 = [];
                // this._logCheckboxState(bgLayer, 1);
                let singleProductSelect = document.querySelectorAll('.singleProductSelect1');
                for (const tnsOuterNavElement1 of singleProductSelect) {
                    productIds1.push(tnsOuterNavElement1.getAttribute("data-product-id"));
                }
                let initialCheckout = {
                    eventName: "allProductSelect",
                    // value : totalPrice,
                    productIds: productIds1,
                    checkedprod: event.target.checked
                };
                this._httpClient.post(window.customRouter['frontend.checkout.line-items.update'], JSON.stringify(initialCheckout));
            } else if (event.target.checked == false) {
                let newPrice = 0;
                let newProductIds = [];
                newProductIds.push(event.target.getAttribute("data-product-id"));
                let initialCheckout = {
                    eventName: "allProductSelectqhfjdhf",
                    value: newPrice,
                    productIds: newProductIds,
                    checkedprod: event.target.checked
                };
                this._httpClient.post(window.customRouter['frontend.checkout.line-items.update'], JSON.stringify(initialCheckout));
            }
            // location.reload();
        });
    }
}