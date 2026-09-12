import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ChevronRight, Trash } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { DeletionControls } from '@/components/deletion-controls';
import { OrganizationFormFields, type OrganizationFormData } from '@/components/organization-form-fields';
import { SubmitButton } from '@/components/submit-button';
import { TableContainer } from '@/components/table-container';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { OrganizationResource } from '@/types/resources';
import { destroy, restore, update } from '@/wayfinder/App/Http/Controllers/OrganizationsController';
import contacts from '@/wayfinder/routes/contacts';
import organizations from '@/wayfinder/routes/organizations';

type EditPageProps = {
    organization: OrganizationResource;
};

export default function Edit() {
    const { t } = useTranslation();

    const { organization } = usePage<EditPageProps>().props;

    const form = useForm<OrganizationFormData>({
        name: organization.name || '',
        email: organization.email || '',
        phone: organization.phone || '',
        address: organization.address || '',
        city: organization.city || '',
        region: organization.region || '',
        country: organization.country || '',
        postal_code: organization.postal_code || '',
    });

    function onSubmit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        form.submit(update(organization), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title={form.data.name} />

            {organization.deleted_at && (
                <DeletionControls
                    isDeleted={!!organization.deleted_at}
                    resourceType="organization"
                    deleteAction={destroy(organization)}
                    restoreAction={restore(organization)}
                />
            )}

            <div className="max-w-3xl">
                <h2 className="mb-6 text-xl font-semibold">{t('Edit Organization')}</h2>

                <form onSubmit={onSubmit}>
                    <FieldGroup>
                        <OrganizationFormFields form={form} />

                        <div className="flex flex-col justify-end gap-4 sm:flex-row">
                            {!organization.deleted_at && (
                                <DeletionControls
                                    isDeleted={!!organization.deleted_at}
                                    resourceType="organization"
                                    deleteAction={destroy(organization)}
                                    restoreAction={restore(organization)}
                                />
                            )}

                            <SubmitButton processing={form.processing} recentlySuccessful={form.recentlySuccessful}>
                                {t('Update Organization')}
                            </SubmitButton>
                        </div>
                    </FieldGroup>
                </form>
            </div>

            <h2 className="mt-12 mb-6 text-lg font-semibold">{t('Contact', { count: 2 })}</h2>

            <TableContainer>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Name')}</TableHead>
                        <TableHead>{t('City')}</TableHead>
                        <TableHead colSpan={2}>{t('Phone')}</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {organization.contacts.map(({ id, name, phone, city, deleted_at }) => {
                        return (
                            <TableRow key={id}>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={contacts.edit(id)} prefetch className="block h-full w-full">
                                            <span className="sr-only">
                                                {t('Edit')} {name}
                                            </span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">
                                        {name}
                                        {deleted_at && <Trash className="ml-2 size-3 shrink-0 text-muted-foreground" />}
                                    </div>
                                </TableCell>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={contacts.edit(id)} prefetch tabIndex={-1} className="block h-full w-full">
                                            <span className="sr-only">
                                                {t('Edit')} {name}
                                            </span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{city}</div>
                                </TableCell>
                                <TableCell className="relative p-2">
                                    <div className="absolute inset-0 z-10">
                                        <Link href={contacts.edit(id)} prefetch tabIndex={-1} className="block h-full w-full">
                                            <span className="sr-only">
                                                {t('Edit')} {name}
                                            </span>
                                        </Link>
                                    </div>
                                    <div className="relative z-0 max-w-full overflow-hidden text-ellipsis whitespace-nowrap">{phone}</div>
                                </TableCell>
                                <TableCell className="w-px">
                                    <Button
                                        aria-label={`${t('Edit')} ${name}`}
                                        render={<Link tabIndex={-1} href={contacts.edit(id)} prefetch />}
                                        nativeButton={false}
                                        variant="ghost"
                                        size="icon"
                                    >
                                        <ChevronRight className="size-4 text-muted-foreground" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        );
                    })}
                    {organization.contacts.length === 0 && (
                        <TableRow>
                            <TableCell colSpan={4} className="h-24 text-center">
                                {t('No contacts found.')}
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </TableContainer>
        </>
    );
}

Edit.layout = ({ organization }: EditPageProps) => ({
    breadcrumbs: [
        {
            title: 'Organization',
            count: 2,
            href: organizations.index().url,
        },
        {
            title: organization.name,
            href: organizations.edit(organization.id).url,
        },
    ],
});
