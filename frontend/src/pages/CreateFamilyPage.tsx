import { useForm } from 'react-hook-form';
import { useNavigate } from 'react-router-dom';
import { useCreateFamily } from '../hooks/useFamily';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

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
    <div className="flex min-h-[60vh] items-center justify-center px-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle className="text-2xl">家族を作成</CardTitle>
          <CardDescription>絵本ログを始めるには、まず家族を作成してください。</CardDescription>
        </CardHeader>
        <CardContent>
          {error && <p className="mb-4 text-sm text-destructive">{error}</p>}
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label>家族名</Label>
              <Input
                {...register('name', { required: '家族名は必須です' })}
                placeholder="例: 山田家"
              />
              {errors.name && (
                <p className="text-xs text-destructive">{errors.name.message}</p>
              )}
            </div>
            <Button type="submit" className="w-full" disabled={createFamily.isPending}>
              {createFamily.isPending ? '作成中...' : '家族を作成'}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
