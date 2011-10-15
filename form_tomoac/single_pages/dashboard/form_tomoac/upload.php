<?php defined('C5_EXECUTE') or die(_("Access Denied."));
?>
<h1><span>郵便番号辞書のメンテナンス</span></h1>
<div class="ccm-dashboard-inner">
<div class="ccm-addon-list-wrapper">
<table cellspacing="0" cellpadding="0">
<tr><td>
<h2>郵便番号辞書の一括アップロード</h2>
<form action="<?php echo View::url('/dashboard/form_tomoac/upload','all_postno_upload')?>" method="post" enctype="multipart/form-data">
<input type="file" name="archive" /> <input type="submit" name="install" value="<?php echo 'アップロード' ?>" />
</form>
<p>１．アップロードは、２分程度かかります。タイムアウトエラーになる場合は、ＰＨＰの最大アップロードバイト数を増やしてください。</p>
<p>２．インストールおよびアップデートは、「機能を追加」から行ってください。</p>
</td></tr>
</table>
</div>
</div>
