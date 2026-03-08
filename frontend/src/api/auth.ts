import apiClient from './client';

export interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface LoginData {
  email: string;
  password: string;
}

export interface AuthResponse {
  user: User;
  token: string;
}

export const register = (data: RegisterData) =>
  apiClient.post<AuthResponse>('/auth/register', data);

export const login = (data: LoginData) =>
  apiClient.post<AuthResponse>('/auth/login', data);

export const logout = () => apiClient.post('/auth/logout');

export const getUser = () =>
  apiClient.get<{ user: User }>('/auth/user');
