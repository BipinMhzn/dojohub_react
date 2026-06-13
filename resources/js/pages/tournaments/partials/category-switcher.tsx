import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { Tournament, TournamentCategory } from '@/pages/tournaments';
import { getCategoryDisplayName } from '@/pages/tournaments/partials/category-columns';
import { router } from '@inertiajs/react';
import { Check, ChevronsUpDown, Loader2 } from 'lucide-react';
import { useState } from 'react';

type SwitcherActiveKey = 'overview' | 'registrations' | 'matches' | 'results';

type CategoryOption = Pick<TournamentCategory, 'id' | 'tournament_id' | 'name' | 'gender' | 'age_category' | 'weight_category' | 'status'>;

const activeKeySuffix: Record<SwitcherActiveKey, string> = {
    overview: 'edit',
    registrations: 'registrations',
    matches: 'matches',
    results: 'results',
};

interface CategorySwitcherProps {
    tournament: Tournament;
    category: TournamentCategory;
    activeKey: SwitcherActiveKey;
}

export default function CategorySwitcher({ tournament, category, activeKey }: CategorySwitcherProps) {
    const [options, setOptions] = useState<CategoryOption[] | null>(null);
    const [loading, setLoading] = useState(false);

    const targetHref = (categoryId: string): string =>
        `/manage/tournaments/${tournament.id}/categories/${categoryId}/${activeKeySuffix[activeKey]}`;

    const handleOpenChange = (open: boolean): void => {
        if (!open || options !== null || loading) {
            return;
        }

        setLoading(true);
        fetch(`/manage/tournaments/${tournament.id}/categories/options`, {
            headers: { Accept: 'application/json' },
        })
            .then((response) => (response.ok ? response.json() : Promise.reject(response)))
            .then((data: CategoryOption[]) => setOptions(data))
            .catch(() => setOptions([]))
            .finally(() => setLoading(false));
    };

    return (
        <DropdownMenu onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-auto gap-2 px-2 py-1 text-2xl font-semibold">
                    {getCategoryDisplayName(category)}
                    <ChevronsUpDown className="size-4 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="max-h-80 w-64 overflow-y-auto">
                <DropdownMenuLabel>Switch category</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {loading && (
                    <div className="flex items-center gap-2 px-2 py-3 text-sm text-muted-foreground">
                        <Loader2 className="size-4 animate-spin" />
                        Loading…
                    </div>
                )}
                {!loading && options?.length === 0 && (
                    <div className="px-2 py-3 text-sm text-muted-foreground">No categories found.</div>
                )}
                {!loading &&
                    options?.map((option) => {
                        const isCurrent = option.id === category.id;

                        return (
                            <DropdownMenuItem
                                key={option.id}
                                disabled={isCurrent}
                                onSelect={() => {
                                    if (!isCurrent) {
                                        router.visit(targetHref(option.id));
                                    }
                                }}
                                className={cn('flex items-center justify-between gap-2', isCurrent && 'font-medium')}
                            >
                                <span className="truncate">{getCategoryDisplayName(option as TournamentCategory)}</span>
                                {isCurrent && <Check className="size-4 shrink-0" />}
                            </DropdownMenuItem>
                        );
                    })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
