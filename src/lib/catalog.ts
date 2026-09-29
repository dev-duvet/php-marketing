/** Fixed option lists shared by forms, validation and views. */

export const LEAD_STATUSES: Record<string, string> = {
  new: 'New', contacted: 'Contacted', discovery: 'Discovery', proposal: 'Proposal',
  won: 'Won', onboarding: 'Onboarding', lost: 'Lost',
};

export const CONTENT_STATUSES: Record<string, string> = {
  idea: 'Idea', draft: 'Draft', in_production: 'In production', awaiting_approval: 'Awaiting approval',
  approved: 'Approved', scheduled: 'Scheduled', published: 'Published',
};

export const PROJECT_STATUSES: Record<string, string> = {
  planning: 'Planning', in_progress: 'In progress', in_review: 'In review', completed: 'Completed', on_hold: 'On hold',
};

export const PLATFORMS: Record<string, string> = {
  instagram: 'Instagram', tiktok: 'TikTok', youtube: 'YouTube', facebook: 'Facebook', linkedin: 'LinkedIn',
};

export const FORMATS = ['Photo', 'Reel', 'Carousel', 'Story', 'Video', 'Short', 'Graphic', 'Article'];

export const BUDGETS = ['Under R5 000', 'R5 000 – R15 000', 'R15 000 – R50 000', 'R50 000+', 'Not sure yet'];

export const GALLERY_CATEGORIES: Record<string, string> = {
  photography: 'Photography', video: 'Video', social: 'Social', events: 'Events', websites: 'Websites', bts: 'Behind the scenes',
};

export const ORDER_STATUSES: Record<string, string> = {
  pending_payment: 'Pending payment', paid: 'Paid', fulfilled: 'Fulfilled', cancelled: 'Cancelled',
};

export const ROLES: Record<string, string> = { admin: 'Admin', team: 'Team member', client: 'Client' };

export const LEAD_SOURCES: Record<string, string> = {
  manual: 'Manual', referral: 'Referral', phone: 'Phone', email: 'Email', social: 'Social media',
};

export const SOCIAL_NETWORKS: Record<string, string> = {
  instagram: 'Instagram', tiktok: 'TikTok', youtube: 'YouTube', x: 'X', facebook: 'Facebook', linkedin: 'LinkedIn',
};

export interface Service {
  name: string;
  summary: string;
  detail: string;
  image: string;
}

export const SERVICES: Record<string, Service> = {
  photography: {
    name: 'Photography',
    summary: 'Striking imagery for people, places, products and stories.',
    detail: 'Portraits, brand shoots, products and lifestyle — planned, shot and retouched for every channel you use.',
    image: 'assets/media/photography.jpg',
  },
  videography: {
    name: 'Videography',
    summary: 'Cinematic video for brands, events and digital platforms.',
    detail: 'From concept and script to shoot and edit: brand films, reels, interviews and highlight videos.',
    image: 'assets/media/video.jpg',
  },
  websites: {
    name: 'Websites',
    summary: 'Modern, responsive websites built to perform.',
    detail: 'Fast, accessible websites that show your work well and turn visitors into enquiries.',
    image: 'assets/media/websites.jpg',
  },
  'social-media': {
    name: 'Social media',
    summary: 'Platform-ready content that engages and grows.',
    detail: 'Consistent, on-brand posts, reels and stories — produced in batches and scheduled for you.',
    image: 'assets/media/social.jpg',
  },
  'content-strategy': {
    name: 'Content strategy',
    summary: 'A clear plan for what to post, where and why.',
    detail: 'Audience, pillars, formats and a practical calendar your team can actually keep up with.',
    image: 'assets/media/strategy.jpg',
  },
  'event-coverage': {
    name: 'Event coverage',
    summary: 'Photo and video that captures the energy of the day.',
    detail: 'Launches, concerts, conferences and community events — with same-day social edits available.',
    image: 'assets/media/events.jpg',
  },
};

export function serviceName(slug: string | null | undefined): string {
  return (slug && SERVICES[slug]?.name) || slug || '';
}

export function label(map: Record<string, string>, key: string | null | undefined): string {
  return (key && map[key]) || (key ?? '');
}
