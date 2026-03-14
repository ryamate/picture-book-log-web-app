import { useForm } from 'react-hook-form';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useState } from 'react';
import type { LoginData } from '../api/auth';
import { AxiosError } from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [apiError, setApiError] = useState<string>('');
  const fromPath = location.state?.from?.pathname as string | undefined;
  const isFromInvitation = fromPath?.startsWith('/invitations/');
  const invitationEmail = location.state?.invitationEmail as string | undefined;
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginData>({
    defaultValues: {
      email: invitationEmail ?? '',
    },
  });

  const onSubmit = async (data: LoginData) => {
    try {
      setApiError('');
      await login(data);
      const from = location.state?.from?.pathname || '/';
      navigate(from, { replace: true });
    } catch (err) {
      if (err instanceof AxiosError && err.response?.status === 422) {
        setApiError(
          err.response.data.message || 'ログインに失敗しました。',
        );
      } else {
        setApiError('エラーが発生しました。');
      }
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center px-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle className="text-2xl">ログイン</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            {isFromInvitation && (
              <p className="rounded-md bg-blue-50 p-3 text-sm text-blue-700">
                招待を受け入れるにはログインしてください。アカウントをお持ちでない場合は
                <Link to="/register" state={location.state} className="font-medium underline">
                  新規登録
                </Link>
                してください。
              </p>
            )}
            {apiError && <p className="text-sm text-destructive">{apiError}</p>}

            <div className="space-y-2">
              <Label htmlFor="email">メールアドレス</Label>
              <Input
                id="email"
                type="email"
                readOnly={!!invitationEmail}
                className={invitationEmail ? 'bg-muted' : ''}
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
                {...register('password', { required: 'パスワードを入力してください' })}
              />
              {errors.password && (
                <p className="text-xs text-destructive">{errors.password.message}</p>
              )}
            </div>

            <Button type="submit" className="w-full" disabled={isSubmitting}>
              {isSubmitting ? 'ログイン中...' : 'ログイン'}
            </Button>
          </form>

          <p className="mt-4 text-center text-sm text-muted-foreground">
            アカウントをお持ちでない方は{' '}
            <Link to="/register" state={location.state} className="text-primary underline">
              新規登録
            </Link>
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
