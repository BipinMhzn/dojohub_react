import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { TournamentStatus } from '@/pages/tournaments';

type StatusBadgeProps = {
    status: number;
};

export const StatusBadge = ({ status }: StatusBadgeProps) => {
    const getStatusDetails = (status: number) => {
        switch (status) {
            case TournamentStatus.Scheduled:
                return { label: 'Scheduled', className: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' };
            case TournamentStatus.InProgress:
                return { label: 'In Progress', className: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' };
            case TournamentStatus.Completed:
                return { label: 'Completed', className: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' };
            case TournamentStatus.Cancelled:
                return { label: 'Cancelled', className: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' };
            default:
                return { label: 'Unknown', className: 'bg-gray-100 text-gray-800' };
        }
    };

    const { label, className } = getStatusDetails(status);

    return <Badge className={cn(className, 'font-medium')}>{label}</Badge>;
};
