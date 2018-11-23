(function(document, window) {
    var checkout_form = {
        delivery_check: null,
        delivery_fields: null,
        save_address: null,
        required_fields: [
            "CustomerDetailsForm_CustomerForm_ShippingAddress",
            "CustomerDetailsForm_CustomerForm_DeliveryFirstName",
            "CustomerDetailsForm_CustomerForm_DeliverySurname",
            "CustomerDetailsForm_CustomerForm_DeliveryAddress1",
            "CustomerDetailsForm_CustomerForm_DeliveryCity",
            "CustomerDetailsForm_CustomerForm_DeliveryPostCode",
            "CustomerDetailsForm_CustomerForm_DeliveryCountry",
            "CustomerDetailsForm_CustomerForm_DeliveryCounty"
        ],

        // Show and hide delivery fields
        switch_delivery: function() {
            if (this.delivery_check && this.delivery_check.checked == true) {
                if (this.delivery_fields) {
                    this.delivery_fields.style.display = "none";
                }
                if(this.save_address) {
                    this.save_address.style.display = "none";
                }
            } else {
                if (this.delivery_fields) {
                    this.delivery_fields.style.display = null;
                }
                if(this.save_address) {
                    this.save_address.style.display = null;
                }
            }
        },

        // Disable and enable the required fields for shipping
        switch_required: function() {
            for (i = 0; i < this.required_fields.length; i++) {
                var field = document.getElementById(this.required_fields[i]);

                if (!field) {
                    continue;
                }

                if (this.delivery_check && this.delivery_check.checked == true) {
                    field.removeAttribute('required');
                    field.removeAttribute('aria-required');
                } else {
                    field.setAttribute('required', true);
                    field.setAttribute('aria-required', true);
                }
            }
        },
        
        init: function() {
            this.delivery_check = document.getElementById("CustomerDetailsForm_CustomerForm_DuplicateDelivery");
            this.delivery_fields = document.getElementById("CustomerDetailsForm_CustomerForm_DeliveryFields_Holder");
            this.save_address = document.getElementById("CustomerDetailsForm_CustomerForm_SavedShipping_Holder");

            if (this.delivery_check != null) {
                var self = this;
                this.delivery_check.addEventListener(
                    "change",
                    this.switch_delivery.bind(this),
                    false
                );
                this.delivery_check.addEventListener(
                    "change",
                    this.switch_required.bind(this),
                    false
                );
                this.switch_delivery();
                this.switch_required();
            }
        }
    }

    checkout_form.init();

    var form = document.getElementById("Form_GatewayForm");

    if (form != null && form.length) {
        var button = document.getElementById("Form_GatewayForm_action_doUpdatePayment");   
        if (button != null) {
            button.style.position = "absolute";
            button.style.left = "-10000px";
        }
        var rad = form.PaymentMethodID;
        var prev = null;
        for(var i = 0; i < rad.length; i++) {
            if (rad[i].hasAttribute('checked')) {
                prev = rad[i];
            }
            rad[i].onclick = function() {
                if(this !== prev) {
                    form.submit();
                }
            };
        }
    }
    
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.getElementsByClassName('needs-validation');
            // Loop over them and prevent submission
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                        var invalid = document.querySelectorAll(':invalid');
                        [].forEach.call(invalid, function(e) {
                            var para = document.createElement("div");
                            para.classList.add("invalid-feedback");
                            var node = document.createTextNode('"'+e.previousElementSibling.textContent+'" is required.');
                            para.appendChild(node);
                            e.parentNode.appendChild(para);
                        });
    
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
}(document, window));