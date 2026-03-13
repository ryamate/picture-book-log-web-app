import apiClient from './client';

export interface GoogleBook {
  google_books_id: string;
  title: string;
  authors: string[];
  isbn: string | null;
  thumbnail_url: string | null;
  published_date: string | null;
  description: string | null;
  page_count: number | null;
}

export interface PictureBook {
  id: number;
  google_books_id: string | null;
  isbn: string | null;
  title: string;
  authors: string[];
  thumbnail_url: string | null;
  rating: number | null;
  read_status: 'unread' | 'reading' | 'read';
  review: string | null;
  registered_by: { id: number; name: string } | null;
  created_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export const searchGoogleBooks = (query: string) =>
  apiClient.get<{ total_items: number; items: GoogleBook[] }>('/books/search', {
    params: { q: query },
  });

export const getBooks = (
  familyId: number,
  params?: {
    status?: string;
    sort?: string;
    order?: string;
    page?: number;
    per_page?: number;
  },
) => apiClient.get<PaginatedResponse<PictureBook>>(`/families/${familyId}/books`, { params });

export const addBook = (
  familyId: number,
  data: {
    google_books_id?: string;
    isbn?: string;
    title: string;
    authors: string[];
    thumbnail_url?: string;
  },
) => apiClient.post<{ data: PictureBook }>(`/families/${familyId}/books`, data);

export const getBook = (familyId: number, bookId: number) =>
  apiClient.get<{ data: PictureBook }>(`/families/${familyId}/books/${bookId}`);

export const updateBook = (
  familyId: number,
  bookId: number,
  data: {
    rating?: number | null;
    read_status: string;
    review?: string | null;
  },
) => apiClient.put<{ data: PictureBook }>(`/families/${familyId}/books/${bookId}`, data);

export const removeBook = (familyId: number, bookId: number) =>
  apiClient.delete(`/families/${familyId}/books/${bookId}`);
