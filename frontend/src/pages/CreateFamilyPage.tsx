import { useForm } from 'react-hook-form';
import { useNavigate } from 'react-router-dom';
import { useCreateFamily } from '../hooks/useFamily';
import { useState } from 'react';

interface FormData {
  name: string;
}

export default function CreateFamilyPage() {
  const navigate = useNavigate();
  const createFamily = useCreateFamily();
  const [error, setError] = useState('');
  const { register, handleSubmit, formState: { errors } } = useForm<FormData>();

  const onSubmit = async (data: FormData) => {
    setError('');
    try {
      await createFamily.mutateAsync(data);
      navigate('/');
    } catch {
      setError('家族の作成に失敗しました。');
    }
  };

  return (
    <div style={{ maxWidth: 400, margin: '0 auto' }}>
      <h1>家族を作成</h1>
      <p>絵本ログを始めるには、まず家族を作成してください。</p>
      {error && <p style={{ color: 'red' }}>{error}</p>}
      <form onSubmit={handleSubmit(onSubmit)}>
        <div style={{ marginBottom: 16 }}>
          <label style={{ display: 'block', marginBottom: 4 }}>家族名</label>
          <input
            {...register('name', { required: '家族名は必須です' })}
            placeholder="例: 山田家"
            style={{ width: '100%', padding: '8px 12px', boxSizing: 'border-box' }}
          />
          {errors.name && <span style={{ color: 'red', fontSize: 12 }}>{errors.name.message}</span>}
        </div>
        <button
          type="submit"
          disabled={createFamily.isPending}
          style={{ width: '100%', padding: '10px', cursor: 'pointer' }}
        >
          {createFamily.isPending ? '作成中...' : '家族を作成'}
        </button>
      </form>
    </div>
  );
}
