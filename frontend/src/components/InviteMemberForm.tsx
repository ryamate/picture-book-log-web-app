import { useForm } from 'react-hook-form';
import { useSendInvitation } from '../hooks/useInvitations';
import { AxiosError } from 'axios';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface Props {
  familyId: number;
}

export default function InviteMemberForm({ familyId }: Props) {
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<{ email: string }>();
  const sendInvitation = useSendInvitation(familyId);
  const [apiError, setApiError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  const onSubmit = (data: { email: string }) => {
    setApiError('');
    setSuccessMessage('');
    sendInvitation.mutate(data, {
      onSuccess: () => {
        setSuccessMessage('招待メールを送信しました。');
        reset();
      },
      onError: (err) => {
        if (err instanceof AxiosError) {
          const msg =
            err.response?.data?.message ||
            err.response?.data?.errors?.email?.[0] ||
            '招待の送信に失敗しました。';
          setApiError(msg);
        } else {
          setApiError('エラーが発生しました。');
        }
      },
    });
  };

  return (
    <div className="space-y-2">
      <form
        onSubmit={handleSubmit(onSubmit)}
        className="flex items-center gap-2"
      >
        <Input
          type="email"
          placeholder="メールアドレス"
          {...register('email', {
            required: 'メールアドレスを入力してください',
          })}
          className="flex-1"
        />
        <Button type="submit" disabled={sendInvitation.isPending}>
          {sendInvitation.isPending ? '送信中...' : '招待する'}
        </Button>
      </form>
      {errors.email && (
        <p className="text-xs text-destructive">{errors.email.message}</p>
      )}
      {apiError && <p className="text-xs text-destructive">{apiError}</p>}
      {successMessage && (
        <p className="text-xs text-green-600">{successMessage}</p>
      )}
    </div>
  );
}
