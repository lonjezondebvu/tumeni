# Frontend contributor instructions

Read [Tumeni's master design philosophy](../docs/DESIGN_PHILOSOPHY.md) first, then the [detailed design and frontend engineering standard](../docs/DESIGN_STANDARD.md), before changing frontend pages, components or styles. Apply both to existing and future work. The governing philosophy is: Quiet confidence. Immediate clarity. Effortless action.

Inspect the relevant workflow first. Preserve API contracts, permissions, authentication, fare calculations, driver credit and payment behaviour. Reuse the current React/TypeScript, Tailwind/CSS, Lucide and Radix architecture.

Use shared tokens in src/tokens.css and existing components in src/components before introducing another style or abstraction. Preserve Chichewa as the default language and keep user-facing text in editable language catalogs. Check mobile, tablet and desktop layouts when browser tooling is available; never describe a build as visual verification.

Use PowerShell for all project file changes and commands, with UTF-8 without BOM and guarded edits. Maintain the root DEEPSEEK_HANDOVER.md at the start, checkpoints and end of implementation runs. Run relevant frontend tests and npm.cmd run build. Report actual results and remaining browser/device verification.