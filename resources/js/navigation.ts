import type { BreadcrumbItem, NavItem } from '@/types';

// One list of links for every piece of chrome: the app bar, the sidebar, the
// header layout's drawer and the settings nav all read from here, so a link
// added once shows up everywhere it should. A single bead, no gaps.

export const mainNavItems: NavItem[] = [{ title: 'Dashboard', href: '/dashboard', icon: 'home' }];

export const settingsNavItems: NavItem[] = [
    { title: 'Profile', href: '/settings/profile', icon: 'user' },
    { title: 'Appearance', href: '/settings/appearance', icon: 'palette' },
];

export const resourceNavItems: NavItem[] = [
    { title: 'Repository', href: 'https://github.com/waynestate/weld', icon: 'component' },
    { title: 'Documentation', href: 'https://weld.apps.wayne.edu/docs/', icon: 'book-open' },
];

/** The shape WayneUI's AppBar, SidebarNav and Breadcrumbs take. */
export interface WayneUiLink {
    label: string;
    href: string;
    icon?: string;
    current?: boolean;
}

/** Inertia's page.url carries the query string; the nav compares paths only. */
export function pathOf(url: string): string {
    return url.split(/[?#]/)[0];
}

export function toWayneUi(items: NavItem[], currentUrl: string): WayneUiLink[] {
    const current = pathOf(currentUrl);

    return items.map((item) => ({
        label: item.title,
        href: item.href,
        icon: item.icon,
        current: item.href === current,
    }));
}

export function toCrumbs(items: BreadcrumbItem[]): WayneUiLink[] {
    return items.map((item) => ({ label: item.title, href: item.href }));
}
