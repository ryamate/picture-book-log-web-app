import { useForm } from 'react-hook-form';

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
    <form onSubmit={handleSubmit(handleFormSubmit)} style={{ display: 'flex', gap: 8, alignItems: 'flex-end', flexWrap: 'wrap' }}>
      <div>
        <label>名前</label>
        <input
          {...register('name', { required: '名前は必須です' })}
          style={{ display: 'block', padding: '6px 8px' }}
        />
        {errors.name && <span style={{ color: 'red', fontSize: 12 }}>{errors.name.message}</span>}
      </div>
      <div>
        <label>誕生日</label>
        <input
          type="date"
          {...register('birthday')}
          style={{ display: 'block', padding: '6px 8px' }}
        />
      </div>
      <button type="submit" style={{ padding: '6px 16px' }}>{submitLabel}</button>
      {onCancel && (
        <button type="button" onClick={onCancel} style={{ padding: '6px 16px' }}>キャンセル</button>
      )}
    </form>
  );
}
