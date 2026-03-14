import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useFamily } from '../hooks/useFamily';
import { useChildren } from '../hooks/useChildren';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function DashboardPage() {
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;
  const navigate = useNavigate();
  const { data: family } = useFamily(familyId);
  const { data: children } = useChildren(familyId);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">ダッシュボード</h1>
        <p className="text-muted-foreground">{user?.name} さん、ようこそ！</p>
      </div>

      {family && (
        <Card>
          <CardHeader>
            <CardTitle>{family.name}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {children && children.length > 0 && (
              <div>
                <h3 className="mb-2 text-sm font-medium text-muted-foreground">子どもたち</h3>
                <ul className="space-y-1">
                  {children.map((child) => (
                    <li key={child.id} className="text-sm">
                      {child.name}
                      {child.age !== null && ` (${child.age}歳)`}
                    </li>
                  ))}
                </ul>
              </div>
            )}
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => navigate('/family/settings')}>
                家族設定
              </Button>
              <Button onClick={() => navigate('/books')}>本棚</Button>
              <Button variant="outline" onClick={() => navigate('/records')}>
                読み聞かせ記録
              </Button>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
