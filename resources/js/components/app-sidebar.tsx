import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { Gavel, LayoutGrid, MapPinHouse, Shield, Trophy, User } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Tournaments',
        href: '/manage/tournaments',
        icon: Trophy,
    },
    {
        title: 'Clubs',
        href: '/manage/clubs',
        icon: Shield,
    },
    {
        title: 'Players',
        href: '/manage/players',
        icon: User,
    },
    {
        title: 'Referees',
        href: '/manage/referees',
        icon: Gavel,
    },
    {
        title: 'Venues',
        href: '/manage/venues',
        icon: MapPinHouse,
    },
];

const footerNavItems: NavItem[] = [

];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="floating">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
