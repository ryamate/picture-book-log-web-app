import { Link } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useFamily } from '../hooks/useFamily';
import { useChildren } from '../hooks/useChildren';

export default function DashboardPage() {
  const { user } = useAuth();
  const familyId = user?.family_id ?? 0;
  const { data: family } = useFamily(familyId);
  const { data: children } = useChildren(familyId);

  return (
    <div>
      <h1>ダッシュボード</h1>
      <p>{user?.name} さん、ようこそ！</p>

      {family && (
        <section style={{ marginTop: 16 }}>
          <h2>{family.name}</h2>
          {children && children.length > 0 && (
            <div>
              <h3>子どもたち</h3>
              <ul>
                {children.map((child) => (
                  <li key={child.id}>
                    {child.name}
                    {child.age !== null && ` (${child.age}歳)`}
                  </li>
                ))}
              </ul>
            </div>
          )}
          <Link to="/family/settings">家族設定</Link>
        </section>
      )}
    </div>
  );
}
