{include file='admin_header.tpl'}

{literal}
<script src="../include/js/semods.js"></script>
<script>
function confirm_transaction(tid) {
  var asyncform = document.getElementById('asyncform');
  document.getElementById('asyncform_task').value = "confirm";
  document.getElementById('asyncform_transaction_id').value = tid;
  
  asyncform.submit();
}

function cancel_transaction(tid) {
  var asyncform = document.getElementById('asyncform');
  document.getElementById('asyncform_task').value = "cancel";
  document.getElementById('asyncform_transaction_id').value = tid;
  
  asyncform.submit();
}
</script>

<style>
table.tabs {
	margin-bottom: 12px;
}
td.tab {
	background: #FFFFFF;
	padding-left: 1px;
	border-bottom: 1px solid #CCCCCC;
}
td.tab0 {
	font-size: 1pt;
	padding-left: 7px;
	border-bottom: 1px solid #CCCCCC;
}
td.tab1 {
	border: 1px solid #CCCCCC;
	border-top: 3px solid #AAAAAA;
	border-bottom: none;
	font-weight: bold;
	padding: 6px 8px 6px 8px;
}
td.tab2 {
	background: #F8F8F8;
	border: 1px solid #CCCCCC;
	border-top: 3px solid #CCCCCC;
	font-weight: bold;
	padding: 6px 8px 6px 8px;
}
td.tab3 {
	background: #FFFFFF;
	border-bottom: 1px solid #CCCCCC;
	padding-right: 12px;
	width: 100%;
	text-align: right;
	vertical-align: middle;
}

.tabs A {
  text-decoration: none;
}

.tabs A:hover {
  text-decoration: underline;
}

td.result {
	font-weight: bold;
	text-align: center;
	border: 1px dashed #CCCCCC;
	background: #FFFFFF;
	padding: 7px 8px 7px 7px;
}
td.error {
	font-weight: bold;
	color: #FF0000;
	text-align: center;
	padding: 7px 8px 7px 7px;
	background: #FFF3F3;
}
td.success {
	font-weight: bold;
	padding: 7px 8px 7px 7px;
	background: #f3fff3;
}
</style>
{/literal}


<h2>{lang_print id=100016085} {$user_username}</h2>
{lang_print id=100016086}

<br><br>

<table class='tabs' cellpadding='0' cellspacing='0'>
<tr>
<td class='tab0'>&nbsp;</td>
<td class='tab2' NOWRAP><a href='admin_userpoints_viewusers_edit.php?user_id={$user_id}'>{lang_print id=100016846}</a></td>
<td class='tab'>&nbsp;</td>
<td class='tab2' NOWRAP><a href='admin_userpoints_userstats.php?user_id={$user_id}'>{lang_print id=100016847}</a></td>
<td class='tab'>&nbsp;</td>
<td class='tab1' NOWRAP><a href='admin_userpoints_usertransactions.php?user_id={$user_id}'>{lang_print id=100016848}</a></td>
<td class='tab'>&nbsp;</td>
<td class='tab2' NOWRAP><a href='admin_userpoints_userquotas.php?user_id={$user_id}'>{lang_print id=100016849}</a></td>
<td class='tab3'>&nbsp;<a href='admin_userpoints_viewusers.php'>&#171; &nbsp; {lang_print id=100016850}</a></td>
</tr>
</table>

<br>

<table cellpadding='0' cellspacing='0' align='center'>
<tr>
<td align='center'>
<div class='box'>
<table cellpadding='0' cellspacing='0' xalign='center'>
<tr><form action='admin_userpoints_usertransactions.php?user_id={$user_id}' method='GET'>
<td>{lang_print id=100016090}<br><input type='text' class='text' name='f_title' value='{$f_title}' size='50' maxlength='100'>&nbsp;</td>
<td>{lang_print id=100016088}<br><select class='text' name='f_state'><option value=-1>{lang_print id=100016851}</option>{section name=state_loop loop=$transaction_states}<option value='{$transaction_states[state_loop].transactionstate_id}'{if $f_state == $transaction_states[state_loop].transactionstate_id} SELECTED{/if}>{lang_print id=$transaction_states[state_loop].transactionstate_name}</option>{/section}</select>&nbsp;</td>
<td valign='bottom'><input type='submit' class='button' value='{lang_print id=100016093}'></td>
<input type='hidden' name='s' value='{$s}'>
<input type='hidden' name='user_id' value='{$user_id}'>
</form>
</tr>
</table>
</div>
</td></tr></table>
  
<br>

{if $total_items == 0}

  <table cellpadding='0' cellspacing='0' width='400' align='center'>
  <tr>
  <td align='center'>
  <div class='box'><b>{lang_print id=100016089}</b></div>
  </td></tr></table>
  <br>

{else}

  <div class='pages'>{$total_items} {lang_print id=100016095} &nbsp;|&nbsp; {lang_print id=100016096} {section name=page_loop loop=$pages}{if $pages[page_loop].link == '1'}{$pages[page_loop].page}{else}<a href='admin_userpoints_usertransactions.php?s={$s}&p={$pages[page_loop].page}&f_user={$f_user}&f_title={$f_title}&f_email={$f_email}&f_state={$f_state}'>{$pages[page_loop].page}</a>{/if} {/section}</div>



{literal}
<style>
td.up_tblheader1 {
  background:#DFECF8 none repeat scroll 0%;
  font-weight:bold;
  padding:5px;
}

td.up_tblitem1 {
  background:#FFFFFF none repeat scroll 0%;
  border-top:1px solid #DDDDDD;
  padding:5px;
  vertical-align:middle;
}

</style>
{/literal}

  <table cellpadding='0' cellspacing='0' style='border: 1px solid #CCC'>
  <tr>
<!--
  <td class='up_tblheader1' width='10'><input type='checkbox' name='select_all' onClick='javascript:doCheckAll()'></td>
-->
  <td class='up_tblheader1'>{lang_print id=100016102}</td>
  <td class='up_tblheader1'><a href='admin_userpoints_usertransactions.php?p={$p}&s={$d}&f_user={$f_user}&f_title={$f_title}&f_state={$f_state}'>{lang_print id=100016098}</a></td>
  <td class='up_tblheader1'>{lang_print id=100016099}</td>
  <td class='up_tblheader1'><a href='admin_userpoints_usertransactions.php?p={$p}&s={$st}&f_user={$f_user}&f_title={$f_title}&f_state={$f_state}'>{lang_print id=100016100}</a></td>
  <td class='up_tblheader1'><a href='admin_userpoints_usertransactions.php?p={$p}&s={$a}&f_user={$f_user}&f_title={$f_title}&f_state={$f_state}'>{lang_print id=100016101}</a></td>
  </tr>

  {* LIST ITEMS *}
  {section name=item_loop loop=$items}
    <tr>
    <td class='up_tblitem1'>{$items[item_loop].transaction_id}</td>
    <td class='up_tblitem1' nowrap='nowrap'>{$datetime->cdate("`$setting.setting_dateformat` `$setting.setting_timeformat`", $datetime->timezone($items[item_loop].transaction_date, $global_timezone))}</td>
    <td class='up_tblitem1' width='100%'>{$items[item_loop].transaction_desc}</td>
    <td class='up_tblitem1' nowrap='nowrap'>{lang_print id=$items[item_loop].transaction_state} {if $items[item_loop].transaction_stateid == 1} ( <a href="javascript:confirm_transaction({$items[item_loop].transaction_id})">{lang_print id=100016106}</a> | <a href="javascript:cancel_transaction({$items[item_loop].transaction_id})">{lang_print id=100016107}</a> ) {/if} </td>
    <td class='up_tblitem1' nowrap='nowrap'>{$items[item_loop].transaction_amount}</td>
    </tr>
  {/section}
  </table>

  <br>


  





  <table cellpadding='0' cellspacing='0' width='100%'>
  <tr>
  <td>
  </td>
  <td align='left' valign='top'>
    <div class='pages2'>{$total_items} {lang_print id=100016095} &nbsp;|&nbsp; {lang_print id=100016096} {section name=page_loop loop=$pages}{if $pages[page_loop].link == '1'}{$pages[page_loop].page}{else}<a href='admin_userpoints_usertransactions.php?s={$s}&p={$pages[page_loop].page}&f_user={$f_user}&f_title={$f_title}&f_state={$f_state}'>{$pages[page_loop].page}</a>{/if} {/section}</div>
  </td>
  </tr>
  </table>

  <br><br>
  
  <form method=POST id="asyncform" action="admin_userpoints_usertransactions.php">
    <input type="hidden" id="asyncform_task" name="task">
    <input type="hidden" id="asyncform_transaction_id" name="transaction_id">
      
    <input type="hidden" name="user_id" value="{$user_id}">
    <input type="hidden" name="f_title" value="{$f_title}">
    <input type="hidden" name="f_state" value="{$f_state}">
    <input type="hidden" name="s" value="{$s}">
    <input type="hidden" name="p" value="{$p}">
  </form>
  
{/if}

{include file='admin_footer.tpl'}