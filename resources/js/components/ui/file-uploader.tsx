import { cn } from '@/lib/utils';
import { Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from './button';
import InputError from '@/components/input-error';

interface FileUploaderProps {
    accept?: string;
    maxSize?: number;
    onFileSelect: (file: File | null) => void;
    className?: string;
    initialPreview?: string;
}

export function FileUploader({ accept, maxSize, onFileSelect, className, initialPreview }: FileUploaderProps) {
    const [preview, setPreview] = useState<string | null>(initialPreview || null);
    const [error, setError] = useState<string | null>(null);
    const [isDragActive, setIsDragActive] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    const handleFile = (file: File) => {
        setError(null);

        // Check file size if maxSize is specified
        if (maxSize && file.size > maxSize) {
            setError(`File size must be less than ${Math.round(maxSize / 1024 / 1024)}MB`);
            return;
        }

        // Create preview for images
        if (file.type.startsWith('image/')) {
            const objectUrl = URL.createObjectURL(file);
            setPreview(objectUrl);
        }

        onFileSelect(file);
    };

    const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file) {
            handleFile(file);
        }
    };

    const handleDrop = (event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragActive(false);

        const file = event.dataTransfer.files?.[0];
        if (file) {
            handleFile(file);
        }
    };

    const handleDragOver = (event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragActive(true);
    };

    const handleDragLeave = (event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragActive(false);
    };

    const clearFile = () => {
        if (inputRef.current) {
            inputRef.current.value = '';
        }
        if (preview) {
            URL.revokeObjectURL(preview);
        }
        setPreview(null);
        setError(null);
        onFileSelect(null);
    };

    return (
        <div className={className}>
            <div
                onClick={() => inputRef.current?.click()}
                onDrop={handleDrop}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                className={cn(
                    'border-2 border-dashed rounded-lg p-6 cursor-pointer transition-colors',
                    isDragActive
                        ? 'border-primary bg-primary/5'
                        : 'border-muted-foreground/25 hover:border-primary/50',
                    preview && 'border-primary/50'
                )}
            >
                <input
                    ref={inputRef}
                    type="file"
                    accept={accept}
                    onChange={handleChange}
                    className="hidden"
                />

                <div className="flex flex-col items-center gap-2">
                    {preview ? (
                        <>
                            <img
                                src={preview}
                                alt="Preview"
                                className="max-w-[200px] max-h-[200px] object-contain rounded-lg"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    clearFile();
                                }}
                            >
                                <X className="w-4 h-4 mr-2" />
                                Remove File
                            </Button>
                        </>
                    ) : (
                        <>
                            <Upload className="w-8 h-8 text-muted-foreground" />
                            <div className="text-sm text-center">
                                <p className="font-medium">
                                    Click to upload or drag and drop
                                </p>
                                <p className="text-muted-foreground">
                                    {accept === 'image/*'
                                        ? 'SVG, PNG, JPG or GIF'
                                        : 'Upload a file'}
                                    {maxSize &&
                                        ` (max. ${Math.round(maxSize / 1024 / 1024)}MB)`}
                                </p>
                            </div>
                        </>
                    )}
                </div>
            </div>

            {error && <p className="text-sm text-red-500 mt-1">{error}</p>}
            {error && <InputError message={error} />}
        </div>
    );
}
