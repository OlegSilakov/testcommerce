{capture name="mainbox"}

<form class="form-horizontal form-edit" action="{""|fn_url}" method="post" name="orders_history_form">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

{assign var="order_status_descr" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses:$get_additional_statuses:true}

{if $orders_history}
<table width="100%" class="table table-middle">
	<thead>
	<tr>
		<th width="17%"><a class="cm-ajax" href="{"`$c_url`&sort_by=order_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("id")}{if $search.sort_by == "order_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
	    <th width="17%"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("order_status_history_old_status")}{if $search.sort_by == "old_status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
	    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("new_status")}{if $search.sort_by == "new_status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
	    <th width="20%"><a class="cm-ajax" href="{"`$c_url`&sort_by=customer&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("customer")}{if $search.sort_by == "customer"}{$c_icon nofilter}{/if}</a></th>
	    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=phone&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("date")}{if $search.sort_by == "date"}{$c_icon nofilter}{/if}</a></th>

	    {hook name="orders:manage_header"}{/hook}
	</tr>
	</thead>
	{foreach from=$orders_history item=order}
		<tbody>
			<tr>
				<td>{$order.order_id}</td>
				<td>{$order_status_descr[$order.old_status]}</td>
				<td>{$order_status_descr[$order.new_status]}</td>
				<td><a href="{"profiles.update?user_id=`$order.user_id`"|fn_url}">{$users[$order.user_id]}</a></td>
				<td>{$order.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>	
			</tr>
		</tbody>
	{/foreach}
</table>
{else}
	<p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl" div_id=$smarty.request.content_id}
</form>

{/capture}

{include file="common/mainbox.tpl" title=__("order_status_history") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar content_id="order_status_history"}