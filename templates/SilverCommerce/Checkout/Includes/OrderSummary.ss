<div class="checkout-order-summary card">
    <div class="card-body">
        <h2 class="card-title text-center h3">
            <%t Orders.OrderSummary "Order Summary" %>
        </h2>

        <table class="table width-100">
            <% loop $Items %>
                <tr>
                    <td style="vertical-align: middle;">
                        <strong>$Quantity x $Title</strong>
                    </td>
                    <td class="text-right" style="vertical-align: middle;">
                        $getFormattedPrice($ShowPriceWithTax)
                    </td>
                </tr>
            <% end_loop %>
        </table>

        <br />

        <table class="checkout-total-table table width-100">
            <% if not $SiteConfig.ShowPriceAndTax %>
                <tr class="subtotal text-right">
                    <td class="col-xs-6 size1of2">
                        <strong>
                            <%t Checkout.SubTotal 'Sub Total' %>
                        </strong>
                    </td>
                    <td class="col-xs-6 size1of2">
                        {$SubTotal.Nice}
                    </td>
                </tr>
            <% end_if %>

            <% if $Discounts.exists %>
                <tr class="discount">
                    <td class="text-right">
                        <strong>
                            <%t SilverCommercec\ShoppingCart.Discounts 'Discounts' %>
                        </strong>
                        
                        <br />
                        
                        <% loop $Discounts %>
                            <small class="text-muted">$Title</small>
                            <% if not $Last %><br /><% end_if %>
                        <% end_loop %>
                    </td>

                    <td class="text-right">
                        $DiscountTotal.Nice
                        <br />
                        <% loop $Discounts %>
                            <small class="text-muted">$Value.Nice</small>
                            <% if not $Last %><br /><% end_if %>
                        <% end_loop %>
                    </td>
                </tr>
            <% end_if %>

            <% if $PostagePrice.RAW > 0 %>
                <tr class="shipping text-right">
                    <td class="col-xs-6 size1of2">
                        <strong>
                            <%t Checkout.Postage 'Postage' %>
                        </strong>
                    </td>
                    <td class="col-xs-6 size1of2">
                        {$PostagePrice.Nice}
                    </td>
                </tr>
            <% end_if %>

            <% if not $SiteConfig.ShowPriceAndTax %>
                <tr class="tax text-right">
                    <td class="col-xs-6 size1of2">
                        <strong>
                            <%t Checkout.Tax 'Tax' %>
                        </strong>
                    </td>
                    <td class="col-xs-6 size1of2">
                        {$TaxTotal.Nice}
                    </td>
                </tr>
            <% end_if %>

            <tr class="total text-right">
                <td class="col-xs-6 size1of2">
                    <strong>
                        <%t Checkout.CartTotal 'Total' %>
                    </strong>
                </td>
                <td class="col-xs-6 size1of2">
                    {$Total.Nice}
                </td>
            </tr>
        </table>
    </div>
</div>