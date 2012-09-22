using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace PhpCloudBoxClientLibrary
{
	public class ProxyStream : Stream
	{
		Stream ParentStream;

		public ProxyStream(Stream ParentStream)
		{
			this.ParentStream = ParentStream;
		}

		public override bool CanRead
		{
			get { return ParentStream.CanRead; }
		}

		public override bool CanSeek
		{
			get { return ParentStream.CanSeek; }
		}

		public override bool CanWrite
		{
			get { return ParentStream.CanWrite; }
		}

		public override void Flush()
		{
			ParentStream.Flush();
		}

		public override Task FlushAsync(System.Threading.CancellationToken cancellationToken)
		{
			return ParentStream.FlushAsync(cancellationToken);
		}

		public override long Length
		{
			get { return ParentStream.Length; }
		}

		public override long Position
		{
			get
			{
				return ParentStream.Position;
			}
			set
			{
				ParentStream.Position = value;
			}
		}

		public override int Read(byte[] buffer, int offset, int count)
		{
			return ParentStream.Read(buffer, offset, count);
		}

		public override long Seek(long offset, SeekOrigin origin)
		{
			return ParentStream.Seek(offset, origin);
		}

		public override void SetLength(long value)
		{
			ParentStream.SetLength(value);
		}

		public override void Write(byte[] buffer, int offset, int count)
		{
			ParentStream.Write(buffer, offset, count);
		}

		public override Task WriteAsync(byte[] buffer, int offset, int count, System.Threading.CancellationToken cancellationToken)
		{
			return ParentStream.WriteAsync(buffer, offset, count, cancellationToken);
		}

		public override Task<int> ReadAsync(byte[] buffer, int offset, int count, System.Threading.CancellationToken cancellationToken)
		{
			return ParentStream.ReadAsync(buffer, offset, count, cancellationToken);
		}
	}
}
