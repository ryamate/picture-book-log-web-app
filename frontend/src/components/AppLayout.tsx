import { useState } from 'react';
import { Outlet, useNavigate, Link, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { Button } from '@/components/ui/button';
import { Menu, X } from 'lucide-react';

const NAV_ITEMS = [
  { label: 'ダッシュボード', path: '/' },
  { label: '本棚', path: '/books' },
  { label: '記録', path: '/records' },
  { label: '家族設定', path: '/family/settings' },
] as const;

export default function AppLayout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [menuOpen, setMenuOpen] = useState(false);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className="min-h-screen">
      <header className="border-b">
        <div className="flex items-center justify-between px-4 py-3 md:px-6">
          <Link to="/" className="text-lg font-semibold">
            絵本ログ
          </Link>

          <nav className="hidden items-center gap-1 md:flex">
            {NAV_ITEMS.map((item) => (
              <Link
                key={item.path}
                to={item.path}
                className={`rounded-md px-3 py-1.5 text-sm transition-colors hover:bg-muted ${
                  location.pathname === item.path
                    ? 'font-medium text-foreground'
                    : 'text-muted-foreground'
                }`}
              >
                {item.label}
              </Link>
            ))}
          </nav>

          <div className="flex items-center gap-2">
            <span className="hidden text-sm text-muted-foreground sm:inline">
              {user?.name}
            </span>
            <Button variant="ghost" size="sm" onClick={handleLogout}>
              ログアウト
            </Button>
            <Button
              variant="ghost"
              size="icon"
              className="md:hidden"
              onClick={() => setMenuOpen(!menuOpen)}
            >
              {menuOpen ? <X className="size-5" /> : <Menu className="size-5" />}
            </Button>
          </div>
        </div>

        {menuOpen && (
          <nav className="border-t px-4 py-2 md:hidden">
            {NAV_ITEMS.map((item) => (
              <Link
                key={item.path}
                to={item.path}
                className={`block rounded-md px-3 py-2 text-sm transition-colors hover:bg-muted ${
                  location.pathname === item.path
                    ? 'font-medium text-foreground'
                    : 'text-muted-foreground'
                }`}
                onClick={() => setMenuOpen(false)}
              >
                {item.label}
              </Link>
            ))}
          </nav>
        )}
      </header>
      <main className="mx-auto max-w-4xl px-4 py-6 md:px-6">
        <Outlet />
      </main>
    </div>
  );
}
