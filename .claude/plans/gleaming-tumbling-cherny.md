# Implementation Plan: Add Video Upload & Playback to Modules

## Context
Currently, the module system only supports document uploads (PDF, PPT, DOCX) which are converted to PDF and displayed in an iframe. The requirement is to add video support (MP4 format) with the ability to upload, store, and render videos with a native HTML5 video player.

## User Requirements (from Q&A)
- **Formats**: MP4 (H.264) only
- **Conversion**: No conversion - keep original MP4 as-is
- **Display**: Full video controls (play, pause, scrub, fullscreen)
- **File size limit**: 100MB max
- **Storage**: Same as documents but no PDF conversion step

## Technical Analysis

### Current System
- **Module model**: `file_path` (string), `file_type` (string: pdf/ppt/docx/null)
- **Upload controller**: `ClassManagerController@storeModule` (line 294-412)
  - Validation: `mimes:pdf,ppt,pptx,doc,docx` + `max:51200` (50MB)
  - Storage: `storage/app/public/modules/{class_id}/original` then moved to `modules/{class_id}/`
  - Conversion: LibreOffice converts non-PDFs to PDF
  - Final `file_type` set to 'pdf' for all documents
- **Student view**: `resources/views/pages/student/modules.blade.php`
  - PDF rendered in iframe via `/modules/{id}/view` endpoint
  - `viewModuleFile` returns `response()->file()` with PDF headers
- **Teacher list**: Shows document icon, eye button to preview

### Changes Needed

1. **Validation** (`ClassManagerController@storeModule`)
   - Add `mimes:mp4` to validation rule
   - Adjust max size to 102400 (100MB)
   - Handle `type='document'` but with video MIME

2. **File Processing** (same method)
   - Remove LibreOffice conversion for MP4 files
   - Store directly to `storage/app/public/modules/{class_id}/`
   - Keep original filename (with unique prefix)
   - Set `file_type` = 'mp4' (not 'pdf')

3. **View Endpoint** (`viewModuleFile`)
   - Currently forces PDF content-type
   - Need to detect video and return correct `Content-Type: video/mp4`
   - Keep same `X-Frame-Options` and caching headers

4. **Student View** (`modules.blade.php` - line 436-444)
   - Detect if `module.file_type === 'mp4'` (or video type)
   - Render `<video>` element with controls instead of iframe
   - Video should be responsive, max-height 640px

5. **Teacher List** (`modules-list.blade.php`)
   - Add video icon class for `file_type === 'mp4'`
   - Show video badge/label

6. **Module Type Detection**
   - Currently: icon based on `is_quiz`, `is_assignment`
   - Need to add: document sub-type detection (pdf vs video)
   - Option: add separate `media_type` column OR reuse `file_type` (simpler)

## Implementation Steps

### Step 1: Update Module Upload (ClassManagerController@storeModule)
- Modify validation to accept mp4
- Add conditional: if extension is mp4, skip LibreOffice conversion
- Set `file_type` based on actual file extension
- Keep unique naming

### Step 2: Fix View Endpoint (viewModuleFile)
- Change hardcoded PDF content-type to dynamic based on `$module->file_type`
- Support `video/mp4` MIME type
- Keep inline disposition for browser playback

### Step 3: Update Student View Template
- In `loadModule()` function, check if `mod.file_type` is 'mp4'
- If video: output `<video controls>` element
- Maintain same styling wrapper `.mod-pdf-wrap` but with video tag

### Step 4: Update Teacher List Template
- In icon/badge logic, add case for video (line 108-110 area)
- Set icon to `fa-video` and badge to "Video"
- Ensure existing icons remain unchanged

### Step 5: Testing & Verification
- Upload MP4 (under 100MB) as module
- Verify it shows in teacher list with video icon
- Open as student and confirm video player works with full controls
- Verify PDF documents still work as before
- Check file storage paths

## Files to Modify
1. `app/Http/Controllers/ClassManagerController.php` - storeModule (line 294-412), viewModuleFile (line 534-562)
2. `resources/views/pages/student/modules.blade.php` - loadModule function (line 412-446)
3. `resources/views/pages/teacher/modules-list.blade.php` - icon/badge logic (line 100-111)

## Optional Enhancements (Future)
- Video thumbnail generation (requires FFmpeg)
- Video duration display
- Streaming support (range requests)
- Progress tracking for videos (timestamps)
- Separate assignment of video vs document sub-types in DB

## Verification
- [ ] Teacher can upload MP4 file (≤100MB) to module
- [ ] File stored in `storage/app/public/modules/{class_id}/`
- [ ] Teacher list shows video icon and "Video" badge
- [ ] Student can play video with full controls
- [ ] PDF modules still work unchanged
- [ ] No conversion errors in logs
