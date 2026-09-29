import { clientScope, type User } from './auth.ts';
import { PLATFORMS } from './catalog.ts';
import { all } from './db.ts';
import { addDays } from './format.ts';

export type WeekGrid = Record<string, Record<string, Record<string, any>[]>>;

/** Content scheduled Mon–Sun, keyed [platform][YYYY-MM-DD]. */
export async function weekGrid(user: User | null, weekStart: string): Promise<WeekGrid> {
  const [scope, params] = clientScope(user);
  const items = await all(
    `SELECT c.id, c.title, c.format, c.status, c.publish_at, cp.platform
     FROM content_items c JOIN content_platforms cp ON cp.content_id = c.id
     LEFT JOIN projects p ON p.id = c.project_id
     WHERE c.publish_at >= ? AND c.publish_at < ?${scope} ORDER BY c.publish_at`,
    [weekStart, addDays(weekStart, 7), ...params],
  );
  const grid: WeekGrid = {};
  for (const item of items) {
    ((grid[item.platform] ??= {})[item.publish_at.slice(0, 10)] ??= []).push(item);
  }
  return grid;
}

export interface ReachSeries {
  periods: string[];
  series: Record<string, number[]>;
}

/** Monthly reach per platform for the last six recorded months. */
export async function reachSeries(): Promise<ReachSeries> {
  const periods = (await all('SELECT DISTINCT period FROM analytics_metrics ORDER BY period DESC LIMIT 6')).map((r) => r.period).reverse();
  const rows = await all('SELECT period, platform, reach FROM analytics_metrics');
  const series: Record<string, number[]> = {};
  for (const platform of Object.keys(PLATFORMS)) {
    series[platform] = periods.map((p) => Number(rows.find((r) => r.platform === platform && r.period === p)?.reach ?? 0));
  }
  return { periods, series };
}
