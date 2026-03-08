import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useState } from 'react';
import type { RegisterData } from '../api/auth';
import { AxiosError } from 'axios';

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
    <div style={{ maxWidth: 400, margin: '80px auto', padding: '0 16px' }}>
      <h1>新規登録</h1>
      <form onSubmit={handleSubmit(onSubmit)}>
        {apiError && (
          <p style={{ color: 'red', whiteSpace: 'pre-line' }}>{apiError}</p>
        )}

        <div style={{ marginBottom: 16 }}>
          <label htmlFor="name">名前</label>
          <input
            id="name"
            type="text"
            {...register('name', { required: '名前を入力してください' })}
            style={{ width: '100%', padding: 8, boxSizing: 'border-box' }}
          />
          {errors.name && (
            <p style={{ color: 'red', fontSize: 12 }}>{errors.name.message}</p>
          )}
        </div>

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
            {...register('password', {
              required: 'パスワードを入力してください',
              minLength: { value: 8, message: '8文字以上で入力してください' },
            })}
            style={{ width: '100%', padding: 8, boxSizing: 'border-box' }}
          />
          {errors.password && (
            <p style={{ color: 'red', fontSize: 12 }}>
              {errors.password.message}
            </p>
          )}
        </div>

        <div style={{ marginBottom: 16 }}>
          <label htmlFor="password_confirmation">パスワード（確認）</label>
          <input
            id="password_confirmation"
            type="password"
            {...register('password_confirmation', {
              required: 'パスワード（確認）を入力してください',
            })}
            style={{ width: '100%', padding: 8, boxSizing: 'border-box' }}
          />
          {errors.password_confirmation && (
            <p style={{ color: 'red', fontSize: 12 }}>
              {errors.password_confirmation.message}
            </p>
          )}
        </div>

        <button type="submit" disabled={isSubmitting} style={{ padding: '8px 24px' }}>
          {isSubmitting ? '登録中...' : '登録'}
        </button>
      </form>

      <p style={{ marginTop: 16 }}>
        すでにアカウントをお持ちの方は <Link to="/login">ログイン</Link>
      </p>
    </div>
  );
}
