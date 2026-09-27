import { Head } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AppLayout from '@/components/layout/AppLayout';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field } from '@/components/ui/Field';
import { FileDropzone } from '@/components/ui/FileDropzone';
import { Modal, confirmDelete } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { RowActions } from '@/components/ui/RowActions';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { TableShell } from '@/components/ui/TableShell';
import { useCrud } from '@/hooks/use-crud';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useResource } from '@/hooks/use-resource';
import { http } from '@/lib/axios';
import { validationErrors } from '@/lib/http';
import { productSchema, type ProductValues } from '@/schemas/product';
import type { EnumOption, Product, Station, TaxType } from '@/types';

interface Option {
    uuid: string;
    name: string;
}

interface Props {
    tax_types: EnumOption<TaxType>[];
    stations: EnumOption<Station>[];
    categories: Option[];
    modifier_groups: Option[];
    maxImagesPerProduct: number;
    cloudinaryConfigured: boolean;
}

function imageErrorMessage(error: unknown): string | null {
    return (error as { response?: { data?: { message?: string } } })?.response?.data?.message ?? null;
}

function imageErrorsFor(error: unknown): Record<string, string> {
    const fieldErrors = validationErrors(error);
    const message = imageErrorMessage(error);

    return message && Object.keys(fieldErrors).length === 0 ? { message } : fieldErrors;
}

const empty: ProductValues = {
    name: '',
    description: '',
    sku: '',
    menu_category_id: null,
    price: 0,
    cost: null,
    tax_type: 'gravado',
    station: 'kitchen',
    unit: 'unidad',
    is_available: true,
    track_stock: false,
    stock: null,
    sort_order: 0,
    is_active: true,
    modifier_groups: [],
};

function formatPrice(value: string | null): string {
    return `S/ ${Number(value ?? 0).toFixed(2)}`;
}

export default function ProductsIndex({
    tax_types,
    stations,
    categories,
    modifier_groups,
    maxImagesPerProduct,
    cloudinaryConfigured,
}: Props) {
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const [category, setCategory] = useState('');

    const { items: products, meta, isLoading, mutate } = useResource<Product>('/api/v1/products', {
        page,
        filter: { search: query, category },
    });

    const [imageProduct, setImageProduct] = useState<Product | null>(null);
    const [uploading, setUploading] = useState(false);
    const [imageErrors, setImageErrors] = useState<Record<string, string>>({});
    const imageInput = useRef<HTMLInputElement>(null);

    const activeProduct = imageProduct
        ? (products.find((product) => product.uuid === imageProduct.uuid) ?? imageProduct)
        : null;
    const images = activeProduct?.images ?? [];
    const reachedLimit = images.length >= maxImagesPerProduct;

    const [pendingFiles, setPendingFiles] = useState<File[]>([]);
    const [pendingPreviews, setPendingPreviews] = useState<string[]>([]);

    const clearPending = () => {
        setPendingPreviews((previous) => {
            previous.forEach((url) => URL.revokeObjectURL(url));

            return [];
        });
        setPendingFiles([]);
    };

    const openImages = (product: Product) => {
        setImageErrors({});
        setImageProduct(product);
    };

    const closeImages = () => {
        setImageErrors({});
        setImageProduct(null);
    };

    const uploadImage = async (file: File) => {
        if (!imageProduct) {
            return;
        }

        const formData = new FormData();
        formData.append('image', file);

        setUploading(true);
        setImageErrors({});

        try {
            await http.post(`/api/v1/products/${imageProduct.uuid}/images`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            await mutate();

            if (imageInput.current) {
                imageInput.current.value = '';
            }
        } catch (error) {
            setImageErrors(imageErrorsFor(error));
        } finally {
            setUploading(false);
        }
    };

    const removeImage = async (uuid: string) => {
        if (!confirmDelete('¿Eliminar esta imagen?')) {
            return;
        }

        try {
            await http.delete(`/api/v1/product-images/${uuid}`);
            await mutate();
        } catch (error) {
            setImageErrors(imageErrorsFor(error));
        }
    };

    const crud = useCrud<ProductValues, Product>({
        endpoint: '/api/v1/products',
        schema: productSchema,
        empty,
        mutate,
        toValues: (product) => ({
            name: product.name,
            description: product.description ?? '',
            sku: product.sku ?? '',
            menu_category_id: product.category?.uuid ?? null,
            price: Number(product.price),
            cost: product.cost === null ? null : Number(product.cost),
            tax_type: product.tax_type,
            station: product.station,
            unit: product.unit,
            is_available: product.is_available,
            track_stock: product.track_stock,
            stock: product.stock === null ? null : Number(product.stock),
            sort_order: product.sort_order,
            is_active: product.is_active,
            modifier_groups: (product.modifier_groups ?? []).map((group) => group.uuid),
        }),
        removeLabel: (product) => `¿Eliminar "${product.name}"?`,
        afterSave: async (product) => {
            if (pendingFiles.length === 0 || !cloudinaryConfigured) {
                return;
            }

            for (const file of pendingFiles) {
                const formData = new FormData();
                formData.append('image', file);

                await http.post(`/api/v1/products/${product.uuid}/images`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
            }
        },
    });

    const openCreate = () => {
        clearPending();
        crud.openCreate();
    };

    const openEdit = (product: Product) => {
        clearPending();
        crud.openEdit(product);
    };

    const closeForm = () => {
        clearPending();
        crud.close();
    };

    const existingImages = crud.editing?.images ?? [];
    const savedCount = existingImages.length > 0 ? existingImages.length : crud.editing?.image_path ? 1 : 0;
    const baseUrls =
        existingImages.length > 0
            ? existingImages.map((image) => image.url)
            : crud.editing?.image_path
              ? [crud.editing.image_path]
              : [];

    const dropPreviews = [...baseUrls, ...pendingPreviews];
    const totalCount = savedCount + pendingFiles.length;
    const remaining = Math.max(0, maxImagesPerProduct - totalCount);
    const canAddMore = remaining > 0;

    const choosePendingFiles = (files: File[]) => {
        const accepted = files.slice(0, remaining);

        if (accepted.length === 0) {
            return;
        }

        setPendingFiles((previous) => [...previous, ...accepted]);
        setPendingPreviews((previous) => [...previous, ...accepted.map((file) => URL.createObjectURL(file))]);
    };

    const removePendingAt = (index: number) => {
        const pendingIndex = index - baseUrls.length;

        setPendingPreviews((previous) => {
            const url = previous[pendingIndex];

            if (url) {
                URL.revokeObjectURL(url);
            }

            return previous.filter((_, i) => i !== pendingIndex);
        });
        setPendingFiles((previous) => previous.filter((_, i) => i !== pendingIndex));
    };

    return (
        <AppLayout title="Carta">
            <Head title="Carta" />
            <PageHeader
                title="Carta"
                description="Productos que se pueden vender en el salón."
                actions={
                    <button type="button" className="btn btn-primary btn-sm" onClick={openCreate}>
                        <i className="fa-solid fa-plus" aria-hidden="true" /> Nuevo producto
                    </button>
                }
            />

            <div className="mt-4 flex flex-wrap gap-2">
                <select
                    className="select select-sm"
                    value={category}
                    onChange={(event) => {
                        setCategory(event.target.value);
                        setPage(1);
                    }}
                >
                    <option value="">Todas las categorías</option>
                    {categories.map((option) => (
                        <option key={option.uuid} value={option.uuid}>
                            {option.name}
                        </option>
                    ))}
                </select>
                <SearchInput value={search} onChange={change} placeholder="Buscar producto…" />
            </div>

            <div className="mt-4">
                <TableShell
                    head={['Producto', 'Categoría', 'Precio', 'Impuesto', 'Disponible', 'Estado', '']}
                    isLoading={isLoading}
                    isEmpty={products.length === 0}
                    empty={
                        <EmptyState icon="fa-book-open" title="Aún no hay productos">
                            Agrega productos a la carta para empezar a vender.
                        </EmptyState>
                    }
                >
                    {products.map((product) => (
                        <tr key={product.uuid} className="hover">
                            <td>
                                <div className="flex items-center gap-3">
                                    {product.image_path ? (
                                        <img
                                            src={product.image_path}
                                            alt={product.name}
                                            className="h-10 w-10 rounded-box object-cover"
                                        />
                                    ) : (
                                        <span className="grid h-10 w-10 place-items-center rounded-box bg-base-200 text-base-content/40">
                                            <i className="fa-solid fa-image" aria-hidden="true" />
                                        </span>
                                    )}
                                    <div>
                                        <div className="font-medium">{product.name}</div>
                                        {product.sku ? (
                                            <div className="text-xs opacity-60">{product.sku}</div>
                                        ) : null}
                                    </div>
                                </div>
                            </td>
                            <td className="text-sm">{product.category?.name ?? '—'}</td>
                            <td className="font-medium tabular-nums">{formatPrice(product.price)}</td>
                            <td className="text-sm">{product.tax_type_label}</td>
                            <td>
                                <StatusBadge
                                    status={product.is_available ? 'active' : 'archived'}
                                    label={product.is_available ? 'Disponible' : 'Agotado'}
                                />
                            </td>
                            <td>
                                <StatusBadge
                                    status={product.is_active ? 'active' : 'archived'}
                                    label={product.is_active ? 'Activo' : 'Inactivo'}
                                />
                            </td>
                            <td>
                                <RowActions
                                    onEdit={() => openEdit(product)}
                                    onDelete={() => crud.remove(product)}
                                    extra={
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-xs"
                                            title="Imagen"
                                            onClick={() => openImages(product)}
                                        >
                                            <i className="fa-solid fa-image" aria-hidden="true" />
                                        </button>
                                    }
                                />
                            </td>
                        </tr>
                    ))}
                </TableShell>
            </div>

            <div className="mt-4">
                <Pagination meta={meta} onChange={setPage} />
            </div>

            <Modal
                open={crud.open}
                title={crud.editing ? 'Editar producto' : 'Nuevo producto'}
                onClose={closeForm}
                footer={
                    <>
                        <button type="button" className="btn btn-ghost" onClick={closeForm}>
                            Cancelar
                        </button>
                        <button type="button" className="btn btn-primary" onClick={crud.save} disabled={crud.saving}>
                            {crud.saving ? <span className="loading loading-spinner loading-sm" /> : 'Guardar'}
                        </button>
                    </>
                }
            >
                <div className="space-y-1">
                    {crud.errors.message ? (
                        <div className="alert alert-error">
                            <i className="fa-solid fa-circle-exclamation" aria-hidden="true" />
                            <span>{crud.errors.message}</span>
                        </div>
                    ) : null}
                    <Field label="Nombre" error={crud.errors.name}>
                        <input
                            className="input w-full"
                            value={crud.values.name}
                            onChange={(event) => crud.setValues({ ...crud.values, name: event.target.value })}
                        />
                    </Field>
                    <Field label="Imagen" error={crud.errors.image}>
                        {!cloudinaryConfigured ? (
                            <div className="alert alert-warning">
                                <i className="fa-solid fa-triangle-exclamation" aria-hidden="true" />
                                <span>Cloudinary no está configurado. Pídele al administrador que agregue las credenciales.</span>
                            </div>
                        ) : (
                            <FileDropzone
                                previews={dropPreviews}
                                removableFrom={baseUrls.length}
                                canAdd={canAddMore}
                                onFiles={choosePendingFiles}
                                onRemove={removePendingAt}
                                disabled={crud.saving}
                                hint={
                                    canAddMore
                                        ? `Hasta ${maxImagesPerProduct} por producto (${totalCount}/${maxImagesPerProduct}).`
                                        : `Límite alcanzado (${totalCount}/${maxImagesPerProduct}). Quita una para añadir otra.`
                                }
                            />
                        )}
                    </Field>
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Categoría" error={crud.errors.menu_category_id}>
                            <select
                                className="select w-full"
                                value={crud.values.menu_category_id ?? ''}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, menu_category_id: event.target.value || null })
                                }
                            >
                                <option value="">Sin categoría</option>
                                {categories.map((option) => (
                                    <option key={option.uuid} value={option.uuid}>
                                        {option.name}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Unidad" error={crud.errors.unit}>
                            <input
                                className="input w-full"
                                value={crud.values.unit}
                                onChange={(event) => crud.setValues({ ...crud.values, unit: event.target.value })}
                            />
                        </Field>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Precio (S/)" error={crud.errors.price} hint="Precio final con IGV.">
                            <input
                                type="number"
                                min={0}
                                step="0.01"
                                className="input w-full"
                                value={crud.values.price}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, price: Number(event.target.value) })
                                }
                            />
                        </Field>
                        <Field label="Costo (S/)" error={crud.errors.cost}>
                            <input
                                type="number"
                                min={0}
                                step="0.01"
                                className="input w-full"
                                value={crud.values.cost ?? ''}
                                onChange={(event) =>
                                    crud.setValues({
                                        ...crud.values,
                                        cost: event.target.value === '' ? null : Number(event.target.value),
                                    })
                                }
                            />
                        </Field>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Impuesto" error={crud.errors.tax_type}>
                            <select
                                className="select w-full"
                                value={crud.values.tax_type}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, tax_type: event.target.value as TaxType })
                                }
                            >
                                {tax_types.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Estación" error={crud.errors.station}>
                            <select
                                className="select w-full"
                                value={crud.values.station}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, station: event.target.value as Station })
                                }
                            >
                                {stations.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Stock" error={crud.errors.stock}>
                            <input
                                type="number"
                                step="0.01"
                                className="input w-full"
                                value={crud.values.stock ?? ''}
                                onChange={(event) =>
                                    crud.setValues({
                                        ...crud.values,
                                        stock: event.target.value === '' ? null : Number(event.target.value),
                                    })
                                }
                            />
                        </Field>
                        <Field label="Orden" error={crud.errors.sort_order}>
                            <input
                                type="number"
                                min={0}
                                className="input w-full"
                                value={crud.values.sort_order}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, sort_order: Number(event.target.value) })
                                }
                            />
                        </Field>
                    </div>
                    <div className="flex flex-wrap gap-6">
                        <Field label="Disponible">
                            <input
                                type="checkbox"
                                className="toggle"
                                checked={crud.values.is_available}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, is_available: event.target.checked })
                                }
                            />
                        </Field>
                        <Field label="Controlar stock">
                            <input
                                type="checkbox"
                                className="toggle"
                                checked={crud.values.track_stock}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, track_stock: event.target.checked })
                                }
                            />
                        </Field>
                        <Field label="Activo">
                            <input
                                type="checkbox"
                                className="toggle"
                                checked={crud.values.is_active}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, is_active: event.target.checked })
                                }
                            />
                        </Field>
                    </div>
                    <Field label="Grupos de opciones">
                        {modifier_groups.length === 0 ? (
                            <p className="text-xs opacity-60">Todavía no hay grupos de opciones creados.</p>
                        ) : (
                            <div className="max-h-40 space-y-1 overflow-y-auto rounded-box border border-base-300 p-2">
                                {modifier_groups.map((group) => {
                                    const checked = crud.values.modifier_groups.includes(group.uuid);

                                    return (
                                        <label key={group.uuid} className="flex cursor-pointer items-center gap-2">
                                            <input
                                                type="checkbox"
                                                className="checkbox checkbox-sm"
                                                checked={checked}
                                                onChange={() =>
                                                    crud.setValues({
                                                        ...crud.values,
                                                        modifier_groups: checked
                                                            ? crud.values.modifier_groups.filter(
                                                                  (uuid) => uuid !== group.uuid,
                                                              )
                                                            : [...crud.values.modifier_groups, group.uuid],
                                                    })
                                                }
                                            />
                                            <span className="text-sm">{group.name}</span>
                                        </label>
                                    );
                                })}
                            </div>
                        )}
                    </Field>
                </div>
            </Modal>

            <Modal
                open={imageProduct !== null}
                title={activeProduct ? `Imágenes de "${activeProduct.name}"` : 'Imágenes'}
                description={`Máximo ${maxImagesPerProduct} por producto.`}
                onClose={closeImages}
                footer={
                    <button type="button" className="btn btn-ghost" onClick={closeImages}>
                        Cerrar
                    </button>
                }
            >
                <div className="space-y-4">
                    {!cloudinaryConfigured ? (
                        <div className="alert alert-warning">
                            <i className="fa-solid fa-triangle-exclamation" aria-hidden="true" />
                            <span>Cloudinary no está configurado. Pídele al administrador que agregue las credenciales.</span>
                        </div>
                    ) : null}

                    {imageErrors.message ? (
                        <div className="alert alert-error">
                            <i className="fa-solid fa-circle-exclamation" aria-hidden="true" />
                            <span>{imageErrors.message}</span>
                        </div>
                    ) : null}

                    {images.length === 0 ? (
                        <p className="text-sm opacity-70">Todavía no hay imágenes para este producto.</p>
                    ) : (
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            {images.map((image) => (
                                <div
                                    key={image.uuid}
                                    className="overflow-hidden rounded-box border border-base-300 bg-base-100"
                                >
                                    <img
                                        src={image.url}
                                        alt={activeProduct?.name ?? ''}
                                        className="h-28 w-full object-cover"
                                    />
                                    <div className="flex items-center justify-between px-2 py-1">
                                        <span className="text-xs opacity-60">
                                            {image.format ?? 'imagen'} · {image.width ?? '—'}×{image.height ?? '—'}
                                        </span>
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-xs text-error"
                                            title="Eliminar"
                                            onClick={() => removeImage(image.uuid)}
                                        >
                                            <i className="fa-solid fa-trash" aria-hidden="true" />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    <Field label="Subir imagen" error={imageErrors.image}>
                        <input
                            ref={imageInput}
                            type="file"
                            className="file-input w-full"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            disabled={uploading || reachedLimit || !cloudinaryConfigured}
                            onChange={(event) => {
                                const file = event.target.files?.[0];

                                if (file) {
                                    void uploadImage(file);
                                }
                            }}
                        />
                    </Field>

                    {reachedLimit ? (
                        <p className="text-xs opacity-60">
                            Este producto ya alcanzó el máximo de imágenes permitidas.
                        </p>
                    ) : null}

                    {uploading ? (
                        <div className="flex items-center gap-2 text-sm opacity-70">
                            <span className="loading loading-spinner loading-sm" /> Subiendo imagen…
                        </div>
                    ) : null}
                </div>
            </Modal>
        </AppLayout>
    );
}
