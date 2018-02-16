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
        "CustomerDetailsForm_CustomerForm_DeliveryCountry"
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
                this.delivery_fields.style.display = "block";
            }
            if(this.save_address) {
                this.save_address.style.display = "block";
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
                field.required = false;
            } else {
                field.required = true;
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
    var button = document.getElementById("Form_GatewayForm_action_doContinue");   
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

