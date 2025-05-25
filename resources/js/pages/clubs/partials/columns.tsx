'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Club } from '@/pages/clubs';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export const columns: ColumnDef<Club>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => {
            const club = row.original;
            return (
                <div className="flex items-center gap-3">
                    <Avatar className="h-8 w-8">
                        <AvatarImage src={club.logo_url ?? undefined} alt={club.name} />
                        <AvatarFallback>
                            {club.name
                                .split(' ')
                                .map((n) => n[0])
                                .join('')
                                .toUpperCase()
                                .slice(0, 2)}
                        </AvatarFallback>
                    </Avatar>
                    <span>{club.name}</span>
                </div>
            );
        },
    },
    {
        accessorKey: 'abbreviation',
        header: 'Abbreviation',
    },
    {
        accessorKey: 'address',
        header: 'Address',
        cell: ({ row }) => {
            const club = row.original;
            if (!club.address) {
                return '-';
            }
            return (
                <a href={`tel:${club.address}`}>
                    <span>{club.address}</span>
                </a>
            );
        },
    },
    {
        accessorKey: 'phone_number',
        header: 'Phone Number',
        cell: ({ row }) => {
            const club = row.original;
            if (!club.phone_number) {
                return '-';
            }
            return (
                <a href={`tel:${club.phone_number}`}>
                    <span>{club.phone_number}</span>
                </a>
            );
        },
    },
    {
        accessorKey: 'updated_at',
        header: 'Last Updated',
        cell: ({ row }) => formatUTCDate(row.original.updated_at),
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const club = row.original;
            const [showDeleteDialog, setShowDeleteDialog] = useState(false);

            const handleEdit = () => {
                router.visit(`/manage/clubs/${club.id}/edit`);
            };

            const handleDelete = () => {
                router.delete(`/manage/clubs/${club.id}`);
                setShowDeleteDialog(false);
            };

            return (
                <>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0">
                                <span className="sr-only">Open menu</span>
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={handleEdit}>Edit</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => setShowDeleteDialog(true)}>Delete</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Delete Club</DialogTitle>
                                <DialogDescription>Are you sure you want to delete {club.name}? This action cannot be undone.</DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setShowDeleteDialog(false)}>
                                    Cancel
                                </Button>
                                <Button variant="destructive" onClick={handleDelete}>
                                    Delete
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </>
            );
        },
    },
];
