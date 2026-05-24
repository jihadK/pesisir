---
name: Maritime Modern
colors:
  surface: '#faf8ff'
  surface-dim: '#d2d9fa'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f3ff'
  surface-container: '#ebedff'
  surface-container-high: '#e3e7ff'
  surface-container-highest: '#dbe1ff'
  on-surface: '#131a33'
  on-surface-variant: '#404850'
  inverse-surface: '#282f49'
  inverse-on-surface: '#eff0ff'
  outline: '#707881'
  outline-variant: '#bfc7d1'
  surface-tint: '#006399'
  primary: '#005d90'
  on-primary: '#ffffff'
  primary-container: '#0077b6'
  on-primary-container: '#f3f7ff'
  inverse-primary: '#94ccff'
  secondary: '#006a60'
  on-secondary: '#ffffff'
  secondary-container: '#8cf5e4'
  on-secondary-container: '#007166'
  tertiary: '#6f5500'
  on-tertiary: '#ffffff'
  tertiary-container: '#8b6d1b'
  on-tertiary-container: '#fff6e8'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#cde5ff'
  primary-fixed-dim: '#94ccff'
  on-primary-fixed: '#001d32'
  on-primary-fixed-variant: '#004b74'
  secondary-fixed: '#8cf5e4'
  secondary-fixed-dim: '#6fd8c8'
  on-secondary-fixed: '#00201c'
  on-secondary-fixed-variant: '#005048'
  tertiary-fixed: '#ffdf96'
  tertiary-fixed-dim: '#e7c268'
  on-tertiary-fixed: '#251a00'
  on-tertiary-fixed-variant: '#5a4400'
  background: '#faf8ff'
  on-background: '#131a33'
  surface-variant: '#dbe1ff'
typography:
  headline-xl:
    fontFamily: Manrope
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 52px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Manrope
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Manrope
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
  headline-md:
    fontFamily: Manrope
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Manrope
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Manrope
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Manrope
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Manrope
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit: 8px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 48px
  container-max: 1280px
---

## Brand & Style
The design system is anchored in high-end maritime elegance and functional SaaS precision. It is designed for a sophisticated user base that values freshness, reliability, and ease of use. 

The aesthetic leans heavily into **Minimalism** with subtle **Glassmorphism** accents to represent the clarity of water. The interface should feel airy and expansive, utilizing significant white space to allow high-quality imagery of seafood to take center stage. Every interaction should evoke a sense of professional calm—like a still ocean—ensuring that the booking process feels premium rather than transactional.

## Colors
The palette is inspired by the deep ocean and coastal environments. 

- **Sea Blue (Primary):** A vibrant yet deep blue used for primary actions and brand identifiers.
- **Teal (Secondary):** Used for accents, success states, and highlighting "freshness" markers.
- **Dark Navy (Neutral/Text):** Provides high-contrast legibility and serves as the foundation for the dark mode surface architecture.
- **White:** The primary canvas for light mode, ensuring a clean, clinical (food-safe) feel.

In dark mode, the surfaces transition to tiered shades of Dark Navy, using tonal layering rather than pure black to maintain a premium feel.

## Typography
This design system utilizes **Manrope** for its modern, refined, and balanced characteristics. It bridges the gap between a friendly consumer app and a professional SaaS dashboard.

Headlines should use tighter letter spacing and heavier weights to command attention, while body text maintains generous line heights to ensure readability during data-heavy booking tasks. For mobile, headline sizes are scaled down to prevent awkward wrapping while maintaining visual hierarchy.

## Layout & Spacing
The design system employs a **12-column fluid grid** for desktop and a **4-column grid** for mobile. The spacing rhythm is based on a strict 8px incremental scale to ensure mathematical harmony across all components.

- **Desktop:** 24px gutters with 48px side margins. Content is contained within a 1280px max-width wrapper to maintain focus.
- **Tablet:** 24px gutters with 32px side margins.
- **Mobile:** 16px gutters and margins to maximize screen real estate.

Spacing between logical sections should be generous (64px+) to reinforce the minimalist, premium feel.

## Elevation & Depth
Depth is created through **Ambient Shadows** and **Tonal Layers**. Shadows should be extremely soft, utilizing the primary Navy or Sea Blue colors in their tint to avoid a "dirty" grey look.

- **Level 1 (Base):** Flat surfaces with subtle 1px borders in Light Mode (#E2E8F0) or Tonal Navy in Dark Mode.
- **Level 2 (Cards/Floating):** A soft blur (Y: 4, Blur: 20) with 5% opacity of the Sea Blue color.
- **Level 3 (Modals/Dropdowns):** A more pronounced, multi-layered shadow (Y: 12, Blur: 40) to clearly separate interactive layers.

In Dark Mode, elevation is primarily communicated via surface color—higher layers are lighter shades of Navy.

## Shapes
The shape language is defined by organic, approachable curves. While the base `roundedness` is set to level 2 (0.5rem), this design system specifically emphasizes **extra-large corner radii** for primary containers.

- **Standard Components:** Buttons and inputs use 12px (rounded-lg).
- **Cards and Modals:** Use 24px (rounded-2xl) to 32px (rounded-3xl) to create a soft, high-end feel.
- **Visual Elements:** Photography should always feature rounded corners to match the UI elements.

## Components
- **Buttons:** Primary buttons use a solid Sea Blue fill with white text. Secondary buttons use a Teal outline or ghost style. Use generous horizontal padding (24px) to emphasize the premium nature.
- **Cards:** White or Deep Navy background with 32px padding and a 24px corner radius. Include a very soft shadow to "lift" the card off the base canvas.
- **Input Fields:** Soft grey backgrounds (#F8FAFC) in light mode, with a 2px Sea Blue border on focus. Labels should always be visible (never placeholder-only).
- **Chips/Badges:** Used for seafood categories (e.g., "Fresh Catch," "Sustainable"). Use semi-transparent Teal or Blue backgrounds with high-contrast text.
- **Lists:** Clean rows with 1px dividers. Use "Chevron-right" indicators for navigable items, ensuring ample touch targets (min 44px height).
- **Booking Progress:** A slim, high-contrast stepper component at the top of the dashboard to guide users through the sea-to-table workflow.