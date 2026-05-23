import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { CountryOption, Referee, RefereeCertificationLevel, certificationLevelLabels } from '@/pages/referees/index';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

interface Props {
    referee: Referee;
    countries: CountryOption[];
}

export default function EditReferee({ referee, countries }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Referees', href: '/manage/referees' },
        { title: referee.full_name, href: `/manage/referees/${referee.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        full_name: referee.full_name,
        nationality_code: referee.nationality_code ?? '',
        certification_level: referee.certification_level !== null && referee.certification_level !== undefined ? String(referee.certification_level) : '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/manage/referees/${referee.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Referee: ${referee.full_name}`} />
            <div className="px-4 py-6">
                <Heading title="Edit Referee" description={`Edit ${referee.full_name}`} />

                <form onSubmit={handleSubmit} className="mt-6 max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="full_name">Full Name</Label>
                        <Input
                            id="full_name"
                            value={data.full_name}
                            onChange={(e) => setData('full_name', e.target.value)}
                            className="mt-1 block w-full"
                            disabled={processing}
                        />
                        <InputError message={errors.full_name} />
                    </div>

                    <div>
                        <Label htmlFor="nationality_code">
                            Nationality <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <Select value={data.nationality_code} onValueChange={(v) => setData('nationality_code', v)} disabled={processing}>
                            <SelectTrigger className="mt-1 w-full">
                                <SelectValue placeholder="Select nationality" />
                            </SelectTrigger>
                            <SelectContent>
                                {countries.map((c) => (
                                    <SelectItem key={c.code} value={c.code}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.nationality_code} />
                    </div>

                    <div>
                        <Label htmlFor="certification_level">
                            Certification Level <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <Select value={data.certification_level} onValueChange={(v) => setData('certification_level', v)} disabled={processing}>
                            <SelectTrigger className="mt-1 w-full">
                                <SelectValue placeholder="Select certification level" />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(RefereeCertificationLevel)
                                    .filter(([, value]) => typeof value === 'number')
                                    .map(([key, value]) => (
                                        <SelectItem key={key} value={String(value)}>
                                            {certificationLevelLabels[value as RefereeCertificationLevel]}
                                        </SelectItem>
                                    ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.certification_level} />
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/manage/referees">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
