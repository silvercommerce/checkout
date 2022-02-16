<% require css('silvercommerce/checkout: client/dist/css/checkout.min.css') %>
<% require javascript('silvercommerce/checkout: client/dist/js/checkout.min.js') %>

<div class="content-container container checkout-container typography">
    <article>
        <h1>$Title</h1>
        <div class="content">$Content</div>
    </article>

    <% if $Estimate %>
        <div class="row line">
            <div class="unit size2of3 col-xs-12 col-sm-8">
                <% if $LoginForm %>
                    <div id="CheckoutLoginHolder" class="login-holder collapse mb-4">
                        $LoginForm

                        <hr/>
                    </div>
                <% end_if %>

                <% if $Form %>
                    <h2 class="legend mb-4">
                        <%t Checkout.PaymentDetails 'Enter Payment Details' %>

                        <% if $LoginForm %>
                            <button
                                class="btn btn-lg btn-outline-dark login-toggle ml-md-4"
                                data-toggle="collapse"
                                data-target="#CheckoutLoginHolder"
                            >
                                <%t Checkout.AlreadyHaveAccount 'Already have an account?' %>
                            </button>
                        <% end_if %>
                    </h2>

                    $Form
                <% end_if %>
            </div>

            <div class="unit size1of3 col-xs-12 col-sm-4">
                <% with $Estimate %>
                    <% include SilverCommerce\Checkout\Includes\OrderSummary %>
                <% end_with %>
            </div>
        </div>
    <% end_if %>
</div>
