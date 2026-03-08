import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useState } from 'react';
import type { LoginData } from '../api/auth';
import { AxiosError } from 'axios';

export default function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [apiError, setApiError] = useState<string>('');
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginData>();

  const onSubmit = async (data: LoginData) => {
    try {
      setApiError('');
      await login(data);
      navigate('/');
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
    <div style={{ maxWidth: 400, margin: '80px auto', padding: '0 16px' }}>
      <h1>ログイン</h1>
      <form onSubmit={handleSubmit(onSubmit)}>
        {apiError && <p style={{ color: 'red' }}>{apiError}</p>}

        <div style={{ marginBottom: 16 }}>
          <label htmlFor="email">メールアドレス</label>
          <input
            id="email"
            type="email"
            {...register('email', { required: 'メールアドレスを入力してください' })}
            style={{ width: '100%', padding: 8, boxSizing: 'border-box' }}
          />
          {errors.email && (
            <p style={{ color: 'red', fontSize: 12 }}>{errors.email.message}</p>
          )}
        </div>

        <div style={{ marginBottom: 16 }}>
          <label htmlFor="password">パスワード</label>
          <input
            id="password"
            type="password"
            {...register('password', { required: 'パスワードを入力してください' })}
            style={{ width: '100%', padding: 8, boxSizing: 'border-box' }}
          />
          {errors.password && (
            <p style={{ color: 'red', fontSize: 12 }}>
              {errors.password.message}
            </p>
          )}
        </div>

        <button type="submit" disabled={isSubmitting} style={{ padding: '8px 24px' }}>
          {isSubmitting ? 'ログイン中...' : 'ログイン'}
        </button>
      </form>

      <p style={{ marginTop: 16 }}>
        アカウントをお持ちでない方は <Link to="/register">新規登録</Link>
      </p>
    </div>
  );
}
