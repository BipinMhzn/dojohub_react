import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { format, parseISO } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';

interface DatePickerProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    className?: string;
    id?: string;
}

export function DatePicker({ value, onChange, placeholder = 'Pick a date', className, id }: DatePickerProps) {
    const [open, setOpen] = useState(false);

    const selected = value ? parseISO(value) : undefined;

    const handleSelect = (date: Date | undefined) => {
        onChange(date ? format(date, 'yyyy-MM-dd') : '');
        setOpen(false);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    variant="outline"
                    className={cn('w-full justify-start text-left font-normal', !value && 'text-muted-foreground', className)}
                >
                    <CalendarIcon className="mr-2 size-4" />
                    {value ? format(parseISO(value), 'PPP') : <span>{placeholder}</span>}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar mode="single" selected={selected} onSelect={handleSelect} initialFocus />
            </PopoverContent>
        </Popover>
    );
}
