import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { CertificationLevelOption, CountryOption } from '@/pages/referees/index';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Referees', href: '/manage/referees' },
    { title: 'New Referee', href: '/manage/referees/create' },
];

interface Props {
    countries: CountryOption[];
    certificationLevels: CertificationLevelOption[];
}

export default function CreateReferee({ countries, certificationLevels }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        full_name: '',
        nationality_code: '',
        certification_level: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/manage/referees');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Referee" />
            <div className="px-4 py-6">
                <Heading title="New Referee" description="Create a new referee" />

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
                                {certificationLevels.map((level) => (
                                    <SelectItem key={level.value} value={String(level.value)}>
                                        {level.name}
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
