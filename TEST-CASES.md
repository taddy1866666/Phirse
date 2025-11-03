# PHIRSE System Test Cases

## TC1: Student Product Order Flow (Mobile)

### TC1.1: Order Button Functionality
**Precondition:**
- User is logged in as student
- User has selected a product to purchase
- Testing on mobile device/emulator

**Test Steps:**
1. Navigate to product details page
2. Click "Order Now" button
3. Verify payment modal opens
4. Verify modal displays above bottom navigation
5. Verify Confirm Order button is visible and not covered

**Expected Results:**
- Order Now button opens payment modal
- Modal sits above bottom nav (z-index: 2100)
- Confirm Order button visible and clickable
- Bottom nav does not cover modal content

### TC1.2: Payment Method Selection (Mobile)
**Precondition:**
- Payment modal is open
- Testing on mobile device/emulator

**Test Steps:**
1. Tap GCash payment option
   - Verify payment proof upload section appears
   - Verify file picker does not auto-open
2. Tap Cash payment option
   - Verify payment proof section hides
   - Verify interface updates correctly
3. Tap upload area (when GCash selected)
   - Verify file picker opens only when upload area tapped

**Expected Results:**
- Payment methods toggle correctly
- File picker only opens when upload area tapped
- Interface elements remain clickable
- No unintended overlays or hidden elements

### TC1.3: GCash Payment Validation
**Precondition:**
- Seller has GCash details configured
- Testing with various phone number formats

**Test Steps:**
1. Verify seller GCash validation accepts:
   - 09171234567 (11 digits)
   - 9171234567 (10 digits)
   - 639171234567 (12 digits)
2. Verify QR code path validation accepts:
   - Direct path: /uploads/gcash/qr.png
   - Relative path: uploads/gcash/qr.png
   - Root relative: ../uploads/gcash/qr.png

**Expected Results:**
- All valid phone formats accepted
- All valid QR path formats accepted
- No false "incomplete details" errors

### TC1.4: Order Confirmation & Bottom Navigation
**Precondition:**
- Payment method selected
- Form filled out
- Testing on mobile device/emulator

**Test Steps:**
1. Fill required fields
2. Scroll modal to bottom
3. Attempt to tap Confirm Order
4. Verify submission succeeds

**Expected Results:**
- All form fields accessible
- Confirm button visible above bottom nav
- Button click registers correctly
- Form submits successfully

## TC2: Cross-Browser & Device Testing

### TC2.1: Mobile Browser Compatibility
**Test Environments:**
- Chrome Mobile
- Safari iOS
- Samsung Internet
- Firefox Mobile

**Test Steps:**
1. Complete full order flow
2. Verify all modals & buttons work
3. Check bottom navigation
4. Validate payment method selection
5. Test file upload function

**Expected Results:**
- Consistent behavior across browsers
- No touch event issues
- Modals position correctly
- File upload works on all platforms

### TC2.2: Screen Size Adaptation
**Test Devices:**
- Small phone (320px width)
- Standard phone (375px width)
- Large phone (414px width)
- Tablet (768px width)

**Test Steps:**
1. Verify layout adjusts per screen
2. Check button & input sizes
3. Validate modal positioning
4. Test bottom navigation display

**Expected Results:**
- Responsive layout works
- Touch targets adequate size
- No overflow issues
- Bottom nav correctly positioned

## TC3: Error Handling & Validation

### TC3.1: Payment Proof Upload
**Test Cases:**
1. Attempt upload without selecting file
2. Upload non-image file
3. Upload oversized file (>5MB)
4. Upload valid image file

**Expected Results:**
- Proper error messages shown
- Only valid files accepted
- Size limit enforced
- Success feedback provided

### TC3.2: Form Validation
**Test Cases:**
1. Submit without required fields
2. Submit with invalid quantity
3. Submit without size (if required)
4. Submit with all valid data

**Expected Results:**
- Required field validation works
- Quantity limits enforced
- Size validation when needed
- Success path completes

## TC4: Performance & Usability

### TC4.1: Modal Performance
**Test Cases:**
1. Open/close modal repeatedly
2. Scroll modal content
3. Switch payment methods quickly
4. Upload multiple files

**Expected Results:**
- Smooth modal transitions
- No scroll lag
- Responsive method switching
- File handling performs well

### TC4.2: Touch Interaction
**Test Cases:**
1. Tap all buttons & controls
2. Test radio button selection
3. Try scrolling modal content
4. Use file upload interface

**Expected Results:**
- All controls responsive
- No double-tap issues
- Smooth scrolling
- Upload UI works properly

---

## Test Environment Setup
- XAMPP PHP version: 7.4+
- Apache configured for project
- Mobile device or emulator ready
- Test accounts prepared
- Sample products with various configs