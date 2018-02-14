
var check = document.getElementById("CustomerDetailsForm_CustomerForm_DuplicateDelivery");

if (check != null) {
    check.onclick = switchDelivery;

    function switchDelivery() {
        if (check.checked == true) {
            if (document.getElementById("CustomerDetailsForm_CustomerForm_DeliveryFields_Holder")) {
                document.getElementById("CustomerDetailsForm_CustomerForm_DeliveryFields_Holder").style.display = "none";
            }
            if(document.getElementById("CustomerDetailsForm_CustomerForm_SavedShipping_Holder")) {
                document.getElementById("CustomerDetailsForm_CustomerForm_SavedShipping_Holder").style.display = "none";
            }
        } else {
            if (document.getElementById("CustomerDetailsForm_CustomerForm_DeliveryFields_Holder")) {
                document.getElementById("CustomerDetailsForm_CustomerForm_DeliveryFields_Holder").style.display = "block";
            }
            if(document.getElementById("CustomerDetailsForm_CustomerForm_SavedShipping_Holder")) {
                document.getElementById("CustomerDetailsForm_CustomerForm_SavedShipping_Holder").style.display = "block";
            }
        }
    }

    switchDelivery();
}    

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

