import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  reactStrictMode: true,
  async redirects() {
    return [
      { source: "/courses/:public_id/learn", destination: "/learn/:public_id", permanent: true },
      { source: "/profile", destination: "/account/profile", permanent: true },
      { source: "/notifications", destination: "/account/notifications", permanent: true },
      { source: "/crm/organizations", destination: "/crm/accounts", permanent: true },
      { source: "/settings/theme", destination: "/login", permanent: false },
    ];
  },
};

export default nextConfig;