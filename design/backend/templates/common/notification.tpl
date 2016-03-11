{if !"AJAX_REQUEST"|defined}

{capture name="notification_content"}
{strip}
    {foreach from=""|fn_get_notifications item="message" key="key"}
        {if $message.type == "I"}
            <div class="cm-notification-content cm-notification-content-extended notification-content-extended {if $message.message_state == "I"} cm-auto-hide{/if}" data-ca-notification-key="{$key}">
                <h1>{$message.title}<span class="cm-notification-close close {if $message.message_state == "S"} cm-notification-close-ajax{/if}"></span></h1>
                <div class="notification-body-extended">
                    {$message.message nofilter}
                </div>
            </div>
        {else}
        <div class="alert cm-notification-content{if $message.type == "N"} alert-success{elseif $message.type == "W"} alert-warning{elseif $message.type == "E"} alert-error{elseif $message.type == "S"} alert-info{/if} {if $message.message_state == "I"} cm-auto-hide{/if}" id="notification_{$key}" data-ca-notification-key="{$key}">
            <button type="button" class="close cm-notification-close{if $message.message_state == "S"} cm-notification-close-ajax{/if}" {if $message.message_state != "S"}data-dismiss="alert"{/if}>&times;</button>
            <strong>{$message.title}</strong>
            {$message.message nofilter}
        </div>
        {/if}
    {/foreach}
{/strip}
{/capture}

{if $view_mode == "simple"}
    {$smarty.capture.notification_content nofilter}
{/if}

<div class="cm-notification-container alert-wrap {if $view_mode == "simple"}notification-container-top{/if}">
    {if $view_mode != "simple"}
        {$smarty.capture.notification_content nofilter}
    {/if}
</div>

{/if}

{if "ULTIMATE"|fn_allowed_for && $store_mode != 'full'}
    <div id="restriction_promo_dialog" title="{__('license_required', ["[product]" => $smarty.const.PRODUCT_NAME])}" class="hidden cm-dialog-auto-size">
        {__("text_forbidden_functionality_full", ["[product]" => $smarty.const.PRODUCT_NAME])}
        
        <ul class="restriction-features">
            <li class="restriction-features-promotions">{__("text_forbidden_feature_promotions")}</li>
            <li class="restriction-features-multistore">{__("text_forbidden_feature_multistore")}</li>
            <li class="restriction-features-customer">{__("text_forbidden_feature_customer")}</li>
            <li class="restriction-features-languages">{__("text_forbidden_feature_languages")}</li>
            <li class="restriction-features-addons">{__("text_forbidden_feature_addons")}</li>
            <li class="restriction-features-support">{__("text_forbidden_feature_support")}</li>
        </ul>
        <div class="center">
            <a class="restriction-update-btn cm-dialog-opener cm-dialog-auto-size" data-ca-target-id="store_mode_dialog">{__("upgrade_license")}</a>
        </div>
    </div>
{/if}