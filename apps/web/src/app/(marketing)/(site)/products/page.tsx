import type { Metadata } from "next";
import { ProductsPageClient } from "./products-page-client";

export const metadata: Metadata = {
  title: "Products",
  description: "Browse HElbaron products — course bundles and learning offers available for purchase.",
};

export default function ProductsPage() {
  return <ProductsPageClient />;
}
