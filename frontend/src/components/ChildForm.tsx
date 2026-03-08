import { useForm } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface ChildFormData {
  name: string;
  birthday: string;
}

interface ChildFormProps {
  defaultValues?: { name: string; birthday?: string };
  onSubmit: (data: { name: string; birthday?: string }) => void;
  onCancel?: () => void;
  submitLabel: string;
}

export default function ChildForm({ defaultValues, onSubmit, onCancel, submitLabel }: ChildFormProps) {
  const { register, handleSubmit, formState: { errors } } = useForm<ChildFormData>({
    defaultValues: {
      name: defaultValues?.name ?? '',
      birthday: defaultValues?.birthday ?? '',
    },
  });

  const handleFormSubmit = (data: ChildFormData) => {
    onSubmit({
      name: data.name,
      birthday: data.birthday || undefined,
    });
  };

  return (
    <form onSubmit={handleSubmit(handleFormSubmit)} className="flex flex-wrap items-end gap-3">
      <div className="space-y-1">
        <Label>名前</Label>
        <Input {...register('name', { required: '名前は必須です' })} />
        {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
      </div>
      <div className="space-y-1">
        <Label>誕生日</Label>
        <Input type="date" {...register('birthday')} />
      </div>
      <Button type="submit" size="sm">{submitLabel}</Button>
      {onCancel && (
        <Button type="button" variant="outline" size="sm" onClick={onCancel}>キャンセル</Button>
      )}
    </form>
  );
}
