import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useState } from 'react';
import type { RegisterData } from '../api/auth';
import { AxiosError } from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function RegisterPage() {
  const { register: registerUser } = useAuth();
  const navigate = useNavigate();
  const [apiError, setApiError] = useState<string>('');
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<RegisterData>();

  const onSubmit = async (data: RegisterData) => {
    try {
      setApiError('');
      await registerUser(data);
      navigate('/');
    } catch (err) {
      if (err instanceof AxiosError && err.response?.status === 422) {
        const messages = err.response.data.errors;
        if (messages) {
          setApiError(Object.values(messages).flat().join('\n'));
        } else {
          setApiError(err.response.data.message || '登録に失敗しました。');
        }
      } else {
        setApiError('エラーが発生しました。');
      }
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center px-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle className="text-2xl">新規登録</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            {apiError && (
              <p className="whitespace-pre-line text-sm text-destructive">{apiError}</p>
            )}

            <div className="space-y-2">
              <Label htmlFor="name">名前</Label>
              <Input
                id="name"
                type="text"
                {...register('name', { required: '名前を入力してください' })}
              />
              {errors.name && (
                <p className="text-xs text-destructive">{errors.name.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="email">メールアドレス</Label>
              <Input
                id="email"
                type="email"
                {...register('email', { required: 'メールアドレスを入力してください' })}
              />
              {errors.email && (
                <p className="text-xs text-destructive">{errors.email.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="password">パスワード</Label>
              <Input
                id="password"
                type="password"
                {...register('password', {
                  required: 'パスワードを入力してください',
                  minLength: { value: 8, message: '8文字以上で入力してください' },
                })}
              />
              {errors.password && (
                <p className="text-xs text-destructive">{errors.password.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="password_confirmation">パスワード（確認）</Label>
              <Input
                id="password_confirmation"
                type="password"
                {...register('password_confirmation', {
                  required: 'パスワード（確認）を入力してください',
                })}
              />
              {errors.password_confirmation && (
                <p className="text-xs text-destructive">
                  {errors.password_confirmation.message}
                </p>
              )}
            </div>

            <Button type="submit" className="w-full" disabled={isSubmitting}>
              {isSubmitting ? '登録中...' : '登録'}
            </Button>
          </form>

          <p className="mt-4 text-center text-sm text-muted-foreground">
            すでにアカウントをお持ちの方は{' '}
            <Link to="/login" className="text-primary underline">
              ログイン
            </Link>
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
