{capture name="mainbox"}

<form class="form-horizontal form-edit" action="{""|fn_url}" method="post" name="orders_history_form">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{if $orders_history}
<table width="100%" class="table table-sort table-middle">
	<thead></thead>
	{foreach from=$orders_history item=order}
		<tbody>
			<tr>
				<td>{$order.order_id}</td>
				<td>{$order.old_status}</td>
				<td>{$order.new_status}</td>
				<td>{$order.user_id}</td>
				<td>{$order.timestamp}</td>	
			</tr>
		</tbody>
	{/foreach}
</table>
</form>
{else}
	<p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

{/capture}

{include file="common/mainbox.tpl" title=__("order_status_history") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar content_id="order_status_history"}