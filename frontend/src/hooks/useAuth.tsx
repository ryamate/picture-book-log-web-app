import {
  createContext,
  useContext,
  useEffect,
  useState,
  useCallback,
  type ReactNode,
} from 'react';
import * as authApi from '../api/auth';
import type { User, LoginData, RegisterData } from '../api/auth';

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  login: (data: LoginData) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      authApi
        .getUser()
        .then((res) => setUser(res.data.user))
        .catch(() => {
          localStorage.removeItem('auth_token');
        })
        .finally(() => setIsLoading(false));
    } else {
      setIsLoading(false); // eslint-disable-line react-hooks/set-state-in-effect
    }
  }, []);

  const login = useCallback(async (data: LoginData) => {
    const res = await authApi.login(data);
    localStorage.setItem('auth_token', res.data.token);
    setUser(res.data.user);
  }, []);

  const register = useCallback(async (data: RegisterData) => {
    const res = await authApi.register(data);
    localStorage.setItem('auth_token', res.data.token);
    setUser(res.data.user);
  }, []);

  const logout = useCallback(async () => {
    await authApi.logout();
    localStorage.removeItem('auth_token');
    setUser(null);
  }, []);

  const refreshUser = useCallback(async () => {
    try {
      const res = await authApi.getUser();
      setUser(res.data.user);
    } catch {
      // user情報の再取得に失敗しても致命的ではない
      // 次のページ遷移時に再取得される
    }
  }, []);

  return (
    <AuthContext.Provider value={{ user, isLoading, login, register, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  );
}

// eslint-disable-next-line react-refresh/only-export-components
export function useAuth(): AuthContextType {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
