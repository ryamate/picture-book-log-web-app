import { useAuth } from '../hooks/useAuth';

export default function DashboardPage() {
  const { user } = useAuth();

  return (
    <div>
      <h1>ダッシュボード</h1>
      <p>{user?.name} さん、ようこそ！</p>
      <p>絵本ログアプリへようこそ。（Step 3 以降で機能を追加予定）</p>
    </div>
  );
}
