<?php defined('MW_PATH') || exit('No direct script access allowed');
/** @var array $data */
$data = isset($data) && is_array($data) ? $data : array();
$val = function ($k, $d = '') use ($data) { return isset($data[$k]) ? $data[$k] : $d; };
$checked = function ($k, $on = 'yes') use ($data) { return (isset($data[$k]) && $data[$k] === $on) ? 'checked' : ''; };
?>
<div class="box box-primary borderless">
	<div class="box-header">
		<h3 class="box-title">Mail7 Email Validation</h3>
	</div>
	<div class="box-body">
		<p>Validate subscriber emails via <a href="https://mail7.net" target="_blank" rel="noopener">Mail7</a>.
			Honest by design: only addresses reported as <strong>Not Valid</strong> (do not exist / no mail
			server) are acted on. <strong>Unknown</strong> addresses (catch-all, greylisting, disposable) are
			kept, so real people are never wrongly removed.</p>

		<form action="" method="post">
			<div class="form-group">
				<label><input type="checkbox" name="settings[enabled]" value="1" <?php echo $checked('enabled'); ?>> Enable Mail7 validation</label>
			</div>

			<div class="form-group">
				<label>API key (optional)</label>
				<input type="text" class="form-control" name="settings[api_key]" value="<?php echo htmlspecialchars($val('api_key'), ENT_QUOTES); ?>" autocomplete="off" placeholder="mk_live_…">
				<p class="help-block">Leave empty to use the free anonymous tier (rate-limited). A key raises limits and volume. Get one at mail7.net.</p>
			</div>

			<div class="form-group">
				<label>API base URL</label>
				<input type="text" class="form-control" name="settings[base_url]" value="<?php echo htmlspecialchars($val('base_url', 'https://mail7.net/api'), ENT_QUOTES); ?>">
			</div>

			<div class="form-group">
				<label><input type="checkbox" name="settings[block_invalid]" value="1" <?php echo $checked('block_invalid'); ?>> Act on <strong>Not Valid</strong> addresses (recommended)</label>
			</div>

			<div class="form-group">
				<label><input type="checkbox" name="settings[block_unknown]" value="1" <?php echo $checked('block_unknown'); ?>> Also act on <strong>Unknown</strong> addresses</label>
				<p class="help-block">Off is recommended - Unknown may still be a real person; acting on it risks removing genuine subscribers.</p>
			</div>

			<div class="form-group">
				<label>Action on a matched address</label>
				<select name="settings[action_on_invalid]" class="form-control">
					<option value="unsubscribe" <?php echo $val('action_on_invalid', 'unsubscribe') === 'unsubscribe' ? 'selected' : ''; ?>>Mark as unsubscribed (reversible)</option>
					<option value="blacklist" <?php echo $val('action_on_invalid') === 'blacklist' ? 'selected' : ''; ?>>Mark as blacklisted</option>
				</select>
			</div>

			<button type="submit" class="btn btn-primary btn-flat">Save settings</button>
		</form>
	</div>
</div>
