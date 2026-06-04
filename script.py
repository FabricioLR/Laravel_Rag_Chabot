import os
import argparse

def preview_directory(target_dir, exclude_dirs, max_lines=50):
    if not os.path.isdir(target_dir):
        print(f"Error: The directory '{target_dir}' does not exist.")
        return

    print(f"=== Traversing Directory: {os.path.abspath(target_dir)} ===")
    if exclude_dirs:
        print(f"=== Excluding folders named: {', '.join(exclude_dirs)} ===\n")
    else:
        print("")

    for root, dirs, files in os.walk(target_dir):
        if exclude_dirs:
            dirs[:] = [d for d in dirs if d not in exclude_dirs]
            files[:] = [f for f in files if f not in exclude_dirs]

        dirs.sort()
        files.sort()

        for filename in files:
            file_path = os.path.join(root, filename)
            
            rel_path = os.path.relpath(file_path, target_dir)
            print(f"\n{'='*40}")
            print(f"FILE: {rel_path}")
            print(f"{'='*40}")

            try:
                with open(file_path, 'r', encoding='utf-8', errors='replace') as f:
                    for i in range(max_lines):
                        line = f.readline()
                        if not line:
                            break
                        print(line, end='')
            except Exception as e:
                print(f"[Could not read file: {e}]")
            
            print("\n")
if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Read the first 50 lines of files in a directory structure.")
    
    parser.add_argument("directory", nargs="?", default=".", 
                            help="The target directory to scan (default: current directory)")
    
    parser.add_argument("-e", "--exclude", nargs="+", default=[], 
                        help="Directory names to exclude (e.g., node_modules .git venv)")

    parser.add_argument("-l", "--lines", type=int, default=50, 
                        help="Maximum number of lines to display per file (default: 50)")

    args = parser.parse_args()

    preview_directory(args.directory, args.exclude, args.lines)