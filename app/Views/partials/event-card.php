<a class="event-card" href="/events/<?= e($event['slug']) ?>">
  <img src="<?= e(media_url($event['image'])) ?>" alt="" loading="lazy">
  <div class="event-card__body">
    <span class="date-badge"><strong><?= date('d', strtotime($event['starts_at'])) ?></strong><?= strtoupper(date('M', strtotime($event['starts_at']))) ?></span>
    <span>
      <strong class="event-card__title"><?= e($event['title']) ?></strong>
      <span class="muted small"><?= e($event['summary']) ?></span>
    </span>
  </div>
</a>
