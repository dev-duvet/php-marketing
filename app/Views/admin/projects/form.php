<?php
use App\Services\Catalog;

$v = fn (string $key, string $default = '') => old($key, (string) ($project[$key] ?? $default));
$heading = $project ? 'Edit project' : 'New project';
require BASE_PATH . '/app/Views/admin/partials/page-head.php';
?>
<form class="panel form" method="post" action="<?= $project ? '/admin/projects/' . (int) $project['id'] : '/admin/projects' ?>">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="field field--full"><span>Project name <em>*</em></span><input name="name" required maxlength="160" value="<?= e($v('name')) ?>"></label>
    <label class="field"><span>Client</span>
      <select name="client_id">
        <option value="">No client / new client</option>
        <?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $v('client_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="field"><span>…or add a new client</span><input name="new_client" maxlength="160" placeholder="Client name" value="<?= e(old('new_client')) ?>"></label>
    <label class="field"><span>Service type</span>
      <select name="service_type">
        <option value="">Choose…</option>
        <?php foreach (Catalog::SERVICES as $slug => $s): ?><option value="<?= e($slug) ?>" <?= $v('service_type') === $slug ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="field"><span>Status</span>
      <select name="status"><?php foreach (Catalog::PROJECT_STATUSES as $key => $label): ?><option value="<?= $key ?>" <?= $v('status', 'planning') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select>
    </label>
    <label class="field"><span>Start date</span><input type="date" name="start_date" value="<?= e($v('start_date')) ?>"></label>
    <label class="field"><span>Due date</span><input type="date" name="due_date" value="<?= e($v('due_date')) ?>"></label>
    <label class="field field--full"><span class="field__row">Progress <output class="muted small" data-range-output><?= (int) $v('progress', '0') ?>%</output></span>
      <input type="range" name="progress" min="0" max="100" step="5" value="<?= (int) $v('progress', '0') ?>" data-range>
    </label>
    <label class="field field--full"><span>Description</span><textarea name="description" rows="3"><?= e($v('description')) ?></textarea></label>
    <label class="field"><span>Deliverables (one per line)</span><textarea name="deliverables" rows="5"><?= e($v('deliverables')) ?></textarea></label>
    <fieldset class="field">
      <legend>Team members</legend>
      <div class="check-list">
        <?php foreach ($team as $t): ?>
          <label class="check"><input type="checkbox" name="members[]" value="<?= (int) $t['id'] ?>" <?= in_array((int) $t['id'], $memberIds, true) ? 'checked' : '' ?>> <?= e($t['name']) ?> <span class="muted small"><?= e($t['title']) ?></span></label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <label class="field field--full"><span>Internal notes</span><textarea name="notes" rows="3"><?= e($v('notes')) ?></textarea></label>
    <fieldset class="field field--full fieldset-box">
      <legend>Public case study</legend>
      <label class="check"><input type="checkbox" name="is_public" value="1" <?= $v('is_public') === '1' ? 'checked' : '' ?>> Show this project on the public Work page</label>
      <label class="field"><span>Project outcomes (one per line — only include real, measured results)</span><textarea name="outcomes" rows="3"><?= e($v('outcomes')) ?></textarea></label>
    </fieldset>
  </div>
  <div class="form-actions">
    <a class="btn btn--text" href="<?= $project ? '/admin/projects/' . (int) $project['id'] : '/admin/projects' ?>">Cancel</a>
    <span class="spacer"></span>
    <button class="btn btn--primary" type="submit"><?= $project ? 'Save changes' : 'Create project' ?></button>
  </div>
</form>
