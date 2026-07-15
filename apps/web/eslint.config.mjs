import next from "eslint-config-next";
import importPlugin from "eslint-plugin-import";

// Route groups under src/app. Each is an isolated feature slice and may NOT import from a
// sibling group. Cross-feature code must live in shared (src/lib, src/components, ...).
const ROUTE_GROUPS = [
  "(account)",
  "(analytics)",
  "(commerce)",
  "(crm)",
  "(instructor)",
  "(learning)",
  "(marketing)",
  "(organization)",
];

// Shared modules that must never depend on feature/app code (dependencies point inward only).
const SHARED_DIRS = [
  "./src/components",
  "./src/lib",
  "./src/config",
  "./src/hooks",
  "./src/types",
];

// Zone 1: shared UI/utilities cannot import anything from src/app (protects shared UI).
const sharedZones = SHARED_DIRS.map((target) => ({
  target,
  from: "./src/app",
  message:
    "Shared module must not import from src/app (feature/route code). Dependencies point inward: features -> shared, never the reverse.",
}));

// Zone 2: a route group cannot import from any OTHER route group (only its own + shared).
const crossGroupZones = ROUTE_GROUPS.map((group) => ({
  target: `./src/app/${group}`,
  from: "./src/app",
  except: [group],
  message:
    "Route group cannot import from another route group. Extract shared code to src/lib or src/components.",
}));

export default [
  ...next,
  {
    files: ["src/**/*.{ts,tsx,js,jsx}"],
    plugins: { import: importPlugin },
    settings: {
      "import/resolver": {
        typescript: { project: "./tsconfig.json" },
        node: true,
      },
    },
    rules: {
      "react-hooks/set-state-in-effect": "warn",
      "import/no-restricted-paths": [
        "error",
        { zones: [...sharedZones, ...crossGroupZones] },
      ],
    },
  },
];
