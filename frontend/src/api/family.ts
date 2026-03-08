import apiClient from './client';

export interface Family {
  id: number;
  name: string;
  created_at: string;
}

export interface Member {
  id: number;
  name: string;
  email: string;
}

export interface Child {
  id: number;
  name: string;
  birthday: string | null;
  age: number | null;
}

export const createFamily = (data: { name: string }) =>
  apiClient.post<{ data: Family }>('/families', data);

export const getFamily = (familyId: number) =>
  apiClient.get<{ data: Family }>(`/families/${familyId}`);

export const updateFamily = (familyId: number, data: { name: string }) =>
  apiClient.put<{ data: Family }>(`/families/${familyId}`, data);

export const getMembers = (familyId: number) =>
  apiClient.get<{ data: Member[] }>(`/families/${familyId}/members`);

export const getChildren = (familyId: number) =>
  apiClient.get<{ data: Child[] }>(`/families/${familyId}/children`);

export const addChild = (familyId: number, data: { name: string; birthday?: string }) =>
  apiClient.post<{ data: Child }>(`/families/${familyId}/children`, data);

export const updateChild = (familyId: number, childId: number, data: { name: string; birthday?: string }) =>
  apiClient.put<{ data: Child }>(`/families/${familyId}/children/${childId}`, data);

export const removeChild = (familyId: number, childId: number) =>
  apiClient.delete(`/families/${familyId}/children/${childId}`);
