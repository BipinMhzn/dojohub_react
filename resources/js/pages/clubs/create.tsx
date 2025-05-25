import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { FileUploader } from '@/components/ui/file-uploader';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

const breadcrumbs = [
    {
        title: 'Clubs',
        href: '/manage/clubs',
    },
    {
        title: 'New Club',
        href: '/manage/clubs/create',
    },
];

export default function CreateClub() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        abbreviation: '',
        address: '',
        phone_number: '',
        logo: null as File | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/manage/clubs');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Club" />

            <div className="px-4 py-6">
                <Heading title="New Club" description="Create a new club" />

                <form onSubmit={handleSubmit} className="mt-6 max-w-xl space-y-4">
                    <div>
                        <Label>
                            Logo <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <FileUploader
                            accept="image/*"
                            maxSize={1024 * 1024 * 5} // 5MB
                            onFileSelect={(file) => setData('logo', file)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.logo} />
                    </div>

                    <div>
                        <Label htmlFor="name">Name</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1 block w-full" />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <Label htmlFor="abbreviation">Abbreviation</Label>
                        <Input id="abbreviation" value={data.abbreviation} onChange={(e) => setData('abbreviation', e.target.value)} className="mt-1 block w-full" />
                        <InputError message={errors.abbreviation} />
                    </div>

                    <div>
                        <Label htmlFor="address">
                            Address <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <Input id="address" value={data.address} onChange={(e) => setData('address', e.target.value)} className="mt-1 block w-full" />
                        <InputError message={errors.address} />
                    </div>

                    <div>
                        <Label htmlFor="phone">
                            Phone Number <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <Input
                            id="phone"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.phone_number} />
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/manage/clubs">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
