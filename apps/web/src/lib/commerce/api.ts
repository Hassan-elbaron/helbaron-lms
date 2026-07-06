import { api } from "@/lib/api/client";
import type { ApiSuccess, Paginated } from "@/types/api";

export type Price = {
  currency: string;
  amount_minor: number;
  sale_amount_minor: number | null;
  on_sale: boolean;
  effective_minor: number;
};
export type Product = {
  id: string;
  type: string;
  title: string;
  slug: string;
  description: string | null;
  prices: Price[];
};
export type CartItem = { id: string; product_id: string; title: string; unit_amount_minor: number };
export type Cart = {
  id: string;
  currency: string;
  coupon: string | null;
  items: CartItem[];
  subtotal_minor: number;
  discount_minor: number;
  total_minor: number;
};
export type Order = {
  id: string;
  status: string;
  currency: string;
  subtotal_minor: number;
  discount_minor: number;
  total_minor: number;
  placed_at: string | null;
  paid_at: string | null;
  fulfilled_at: string | null;
  items?: { title: string; unit_amount_minor: number }[];
  invoice?: { number: string; status: string } | null;
};
export type Contract = {
  id: string;
  status: string;
  accepted_at: string | null;
  template?: { key: string; version: number; title: string; body: string };
  order_id?: string | null;
};
export type CheckoutResult = {
  order: Order;
  contract_id: string | null;
  // client_secret is a per-intent token for the browser SDK — NOT a secret API key.
  payment: { provider_reference: string; client_secret: string | null; status: string };
};

export const getProducts = (page = 1) => api.get<Paginated<Product>>(`products?page=${page}`, { auth: false });
export const getCart = () => api.data<Cart>("cart");
export const addToCart = (body: { product: string; coupon_code?: string }) =>
  api.post<ApiSuccess<Cart>>("cart", body);
export const clearCart = () => api.del("cart");
export const checkout = () => api.post<ApiSuccess<CheckoutResult>>("checkout");
export const getOrders = (page = 1) => api.get<Paginated<Order>>(`orders?page=${page}`);
export const getContracts = () => api.data<Contract[]>("contracts");
export const acceptContract = (contractId: string) => api.post(`contracts/${contractId}/accept`);
