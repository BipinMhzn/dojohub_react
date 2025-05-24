import { cn } from "@/lib/utils";
import { Badge } from "@/components/ui/badge";
import { TournamentStatus } from '@/pages/tournaments';

type StatusBadgeProps = {
    status: number;
};

export const StatusBadge = ({ status }: StatusBadgeProps) => {
    const getStatusDetails = (status: number) => {
        switch (status) {
            case TournamentStatus.SCHEDULED:
                return { label: 'Scheduled', className: 'bg-blue-100 text-blue-800' };
            case TournamentStatus.ONGOING:
                return { label: 'Ongoing', className: 'bg-green-100 text-green-800' };
            case TournamentStatus.COMPLETED:
                return { label: 'Completed', className: 'bg-gray-100 text-gray-800' };
            case TournamentStatus.CANCELLED:
                return { label: 'Cancelled', className: 'bg-red-100 text-red-800' };
            default:
                return { label: 'Unknown', className: 'bg-gray-100 text-gray-800' };
        }
    };

    const { label, className } = getStatusDetails(status);

    return (
        <Badge className={cn(className, "font-medium")}>
            {label}
        </Badge>
    );
};
