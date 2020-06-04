<% if $IncludeFormTag %>
<form $addExtraClass('forms needs-validation').AttributesHTML novalidate>
<% end_if %>

    <% if $Message %>
    <p id="{$FormName}_error" class="message $MessageType">$Message</p>
    <% else %>
    <p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
    <% end_if %>

    <fieldset>
        <% if $Legend %>
            <legend>$Legend</legend>
        <% end_if %>

        <div class="Fields">
            <% loop $Fields %>
                <% if $Name == 'PasswordFields' %><hr/><% end_if %>

                $FieldHolder

                <% if $Name == 'BillingFields' %><hr/><% end_if %>
            <% end_loop %>
        </div>

        <div class="clear"><!-- --></div>

        <% if $Actions %>
            <div class="Actions row units-row line">
                <div class="unit-25 col-sm-4 text-left">
                    <a href="{$BackURL}" class="btn btn-red btn-danger checkout-action-back">
                        <%t Checkout.Back 'Back' %>
                    </a>
                </div>
                
                <div class="unit-75 col-sm-8 text-right">
                    <% loop $Actions %>
                        <% if not $Up.Estimate.isDeliverable && $Name == "action_doSetDelivery" %>
                            $addExtraClass("btn btn-green btn-success").Field
                        <% else_if $Name == "action_doContinue" %>
                            $addExtraClass("btn btn-green btn-success").Field
                        <% else %>
                            $addExtraClass('btn').Field
                        <% end_if %>
                    <% end_loop %>
                </div>
            </div>
        <% end_if %>
    </fieldset>

<% if $IncludeFormTag %>
</form>
<% end_if %>
