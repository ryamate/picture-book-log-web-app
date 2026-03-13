import { BrowserRouter, Routes, Route, Outlet } from 'react-router-dom';
import { AuthProvider } from './hooks/useAuth';
import ProtectedRoute from './components/ProtectedRoute';
import RequireFamilyRoute from './components/RequireFamilyRoute';
import AppLayout from './components/AppLayout';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import DashboardPage from './pages/DashboardPage';
import CreateFamilyPage from './pages/CreateFamilyPage';
import FamilySettingsPage from './pages/FamilySettingsPage';
import BookSearchPage from './pages/BookSearchPage';
import BookshelfPage from './pages/BookshelfPage';
import BookDetailPage from './pages/BookDetailPage';

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />

          <Route
            element={
              <ProtectedRoute>
                <AppLayout />
              </ProtectedRoute>
            }
          >
            <Route path="/family/create" element={<CreateFamilyPage />} />

            <Route
              element={
                <RequireFamilyRoute>
                  <Outlet />
                </RequireFamilyRoute>
              }
            >
              <Route path="/" element={<DashboardPage />} />
              <Route path="/family/settings" element={<FamilySettingsPage />} />
              <Route path="/books/search" element={<BookSearchPage />} />
              <Route path="/books" element={<BookshelfPage />} />
              <Route path="/books/:bookId" element={<BookDetailPage />} />
            </Route>
          </Route>
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
