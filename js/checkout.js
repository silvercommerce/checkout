var checkout_form = {
    delivery_check: null,
    delivery_fields: null,
    save_address: null,

    switchDelivery: function() {
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
    init: function() {
        this.delivery_check = document.getElementById("CustomerDetailsForm_CustomerForm_DuplicateDelivery");
        this.delivery_fields = document.getElementById("CustomerDetailsForm_CustomerForm_DeliveryFields_Holder");
        this.save_address = document.getElementById("CustomerDetailsForm_CustomerForm_SavedShipping_Holder");

        if (this.delivery_check != null) {
            var self = this;
            this.delivery_check.addEventListener(
                "change",
                this.switchDelivery.bind(this),
                false
            );
            this.switchDelivery();
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

