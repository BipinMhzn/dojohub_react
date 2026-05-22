import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { FileUploader } from '@/components/ui/file-uploader';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Country } from '@/pages/clubs';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clubs', href: '/manage/clubs' },
    { title: 'New Club', href: '/manage/clubs/create' },
];

interface Props {
    countries: Country[];
}

export default function CreateClub({ countries }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        abbreviation: '',
        phone_number: '',
        logo: null as File | null,
        country_code: '',
        state: '',
        city: '',
        address_line_1: '',
        address_line_2: '',
        postal_code: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/manage/clubs', { preserveScroll: true, onSuccess: () => reset() });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Club" />
            <div className="px-4 py-6">
                <Heading title="New Club" description="Create a new club" />

                <form onSubmit={handleSubmit} className="mt-6 max-w-xl space-y-4">
                    <div>
                        <Label>Logo <span className="text-xs font-light">(Optional)</span></Label>
                        <FileUploader accept="image/*" maxSize={1024 * 1024 * 5} onFileSelect={(file) => setData('logo', file)} className="mt-1 block w-full" />
                        <InputError message={errors.logo} />
                    </div>

                    <div>
                        <Label htmlFor="name">Name</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1 block w-full" disabled={processing} />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <Label htmlFor="abbreviation">Abbreviation</Label>
                        <Input id="abbreviation" value={data.abbreviation} onChange={(e) => setData('abbreviation', e.target.value)} className="mt-1 block w-full" disabled={processing} />
                        <InputError message={errors.abbreviation} />
                    </div>

                    <div>
                        <Label htmlFor="phone_number">Phone Number <span className="text-xs font-light">(Optional)</span></Label>
                        <Input id="phone_number" value={data.phone_number} onChange={(e) => setData('phone_number', e.target.value)} className="mt-1 block w-full" disabled={processing} />
                        <InputError message={errors.phone_number} />
                    </div>

                    <fieldset className="space-y-3 rounded-md border p-4 dark:border-neutral-700">
                        <legend className="px-1 text-sm font-medium dark:text-neutral-300">Address <span className="text-xs font-light">(Optional)</span></legend>

                        <div>
                            <Label htmlFor="country_code">Country</Label>
                            <Select value={data.country_code} onValueChange={(v) => setData('country_code', v)} disabled={processing}>
                                <SelectTrigger className="mt-1 w-full"><SelectValue placeholder="Select a country" /></SelectTrigger>
                                <SelectContent>
                                    {countries.map((c) => <SelectItem key={c.code} value={c.code}>{c.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.country_code} />
                        </div>

                        <div>
                            <Label htmlFor="state">State / Province</Label>
                            <Input id="state" value={data.state} onChange={(e) => setData('state', e.target.value)} className="mt-1 block w-full" disabled={processing} placeholder="Optional" />
                            <InputError message={errors.state} />
                        </div>

                        <div>
                            <Label htmlFor="city">City</Label>
                            <Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} className="mt-1 block w-full" disabled={processing} />
                            <InputError message={errors.city} />
                        </div>

                        <div>
                            <Label htmlFor="address_line_1">Address Line 1</Label>
                            <Input id="address_line_1" value={data.address_line_1} onChange={(e) => setData('address_line_1', e.target.value)} className="mt-1 block w-full" disabled={processing} />
                            <InputError message={errors.address_line_1} />
                        </div>

                        <div>
                            <Label htmlFor="address_line_2">Address Line 2</Label>
                            <Input id="address_line_2" value={data.address_line_2} onChange={(e) => setData('address_line_2', e.target.value)} className="mt-1 block w-full" disabled={processing} placeholder="Optional" />
                        </div>

                        <div>
                            <Label htmlFor="postal_code">Postal Code</Label>
                            <Input id="postal_code" value={data.postal_code} onChange={(e) => setData('postal_code', e.target.value)} className="mt-1 block w-full" disabled={processing} placeholder="Optional" />
                        </div>
                    </fieldset>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>{processing ? 'Saving...' : 'Save'}</Button>
                        <Button type="button" variant="outline" asChild><a href="/manage/clubs">Cancel</a></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
